<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cases;
use App\Models\ClientOrganization;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function mis(Request $request)
    {
        $orgs    = ClientOrganization::orderBy('name')->get();
        $orgId   = $request->input('org_id');
        $perPage = in_array((int) $request->input('per_page'), [10, 25, 50]) ? (int) $request->input('per_page') : 15;

        $baseQuery = Cases::with('clientOrganization')
            ->when($orgId, fn($q) => $q->where('client_organization_id', $orgId));

        if ($request->boolean('export')) {
            return $this->exportCsv((clone $baseQuery)->orderByDesc('created_at')->get(), 'admin');
        }

        // Calculate summary statistics across the full filtered dataset (not just the current page)
        $allForStats = (clone $baseQuery)->get(['status', 'case_start_date', 'report_closed_date']);
        $withTat     = $allForStats->filter(fn($c) => $c->case_start_date && $c->report_closed_date);

        $summaryStats = [
            'total'     => $allForStats->count(),
            'completed' => $allForStats->filter(fn($c) => str_starts_with($c->status, 'Completed'))->count(),
            'active'    => $allForStats->filter(fn($c) => !str_starts_with($c->status, 'Completed'))->count(),
            'avg_tat'   => $withTat->count()
                ? round($withTat->avg(fn($c) => $c->case_start_date->diffInDays($c->report_closed_date)), 1)
                : null,
        ];

        $cases = $baseQuery->orderByDesc('created_at')->paginate($perPage)->withQueryString();

        return view('admin.reports.mis', compact('cases', 'orgs', 'orgId', 'perPage', 'summaryStats'));
    }

    public function updateCaseDate(Request $request, Cases $case)
    {
        $request->validate(['case_start_date' => 'required|date']);
        $case->update(['case_start_date' => $request->case_start_date]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Case start date updated.');
    }

    private function exportCsv($cases, string $type): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filename = 'mis_report_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($cases) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'Client Name', 'Candidate Name', 'Case Number',
                'Case Create Date', 'Case Start Date', 'Report Closed Date',
                'Status', 'TAT (Days)',
            ]);

            foreach ($cases as $case) {
                fputcsv($out, [
                    $case->clientOrganization?->name ?? '—',
                    $case->candidate_name,
                    $case->case_number,
                    $case->created_at?->format('d/m/Y') ?? '—',
                    $case->case_start_date?->format('d/m/Y') ?? '—',
                    $case->report_closed_date?->format('d/m/Y') ?? '—',
                    $case->status,
                    $this->calcTat($case),
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function calcTat(Cases $case): string
    {
        if (!$case->case_start_date || !$case->report_closed_date) {
            return '—';
        }

        return (string) $case->case_start_date->diffInDays($case->report_closed_date);
    }
}
