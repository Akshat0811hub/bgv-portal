<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Cases;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function mis(Request $request)
    {
        $perPage = in_array((int) $request->input('per_page'), [5, 10, 25]) ? (int) $request->input('per_page') : 10;
        $orgId   = auth()->user()->client_organization_id;

        $casesQuery = Cases::where('client_organization_id', $orgId)
            ->orderByDesc('created_at');

        if ($request->boolean('export')) {
            return $this->exportCsv($casesQuery->get());
        }

        $cases = $casesQuery->paginate($perPage)->withQueryString();

        return view('client.reports.mis', compact('cases', 'perPage'));
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
