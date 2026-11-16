<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Cases;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function mis(Request $request)
    {
        $perPage = in_array((int) $request->input('per_page'), [5, 10, 25, 50]) ? (int) $request->input('per_page') : 10;
        $orgId   = auth()->user()->client_organization_id;

        $baseQuery = Cases::where('client_organization_id', $orgId);

        if ($request->boolean('export')) {
            return $this->exportCsv((clone $baseQuery)->orderByDesc('created_at')->get());
        }

        // Calculate summary counters across the entire organization dataset, not just the current page
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

        return view('client.reports.mis', compact('cases', 'perPage', 'summaryStats'));
    }

    private function exportCsv($cases): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filename = 'mis_report_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($cases) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'Candidate Name', 'Case Number',
                'Case Create Date', 'Case Start Date', 'Report Closed Date',
                'Status', 'TAT (Days)',
            ]);

            foreach ($cases as $case) {
                fputcsv($out, [
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
