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
        $orgs  = ClientOrganization::orderBy('name')->get();
        $orgId = $request->input('org_id');

        $cases = Cases::with('clientOrganization')
            ->when($orgId, fn($q) => $q->where('client_organization_id', $orgId))
            ->orderByDesc('created_at')
            ->get();

        if ($request->boolean('export')) {
            return $this->exportCsv($cases, 'admin');
        }

        return view('admin.reports.mis', compact('cases', 'orgs', 'orgId'));
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
