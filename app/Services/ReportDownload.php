<?php

namespace App\Services;

use App\Exceptions\ReportDocumentException;
use App\Models\Cases;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ReportDownload
{
    // Each part stays below serverless response limits, including binary encoding.
    public const PART_BYTES = 1_000_000;

    public function response(Cases $case, string $pdf)
    {
        $filename = 'BGV_Report_'.Str::slug($case->candidate_name ?: 'candidate').'.pdf';
        $chunked = config('reports.chunked_downloads', (bool) (getenv('VERCEL') || getenv('VERCEL_ENV')));
        if (! $chunked || strlen($pdf) <= 3_000_000) {
            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                'Cache-Control' => 'private, no-store',
            ]);
        }

        $download = $this->prepare($case, $pdf, $filename);

        return response()->view('reports.download', [
            'case' => $case,
            'download' => $download,
            'backUrl' => auth()->user()->isAdmin()
                ? route('admin.cases.show', $case)
                : route('client.cases.show', $case),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function prepare(Cases $case, string $pdf, string $filename): object
    {
        if (! Schema::hasTable('report_downloads') || ! Schema::hasTable('report_download_parts')) {
            throw new ReportDocumentException('The complete report is ready, but large report downloads need the latest database migration. Please ask your administrator to run php artisan migrate --force.');
        }

        $download = (object) [
            'id' => (string) Str::uuid(),
            'user_id' => auth()->id(),
            'case_id' => $case->id,
            'filename' => $filename,
            'parts' => (int) ceil(strlen($pdf) / self::PART_BYTES),
            'byte_size' => strlen($pdf),
            'sha256' => hash('sha256', $pdf),
            'expires_at' => now()->addMinutes(30),
        ];

        DB::transaction(function () use ($download, $pdf) {
            DB::table('report_downloads')->where('expires_at', '<=', now())->delete();
            DB::table('report_downloads')->insert((array) $download);
            for ($part = 0; $part < $download->parts; $part++) {
                DB::table('report_download_parts')->insert([
                    'report_download_id' => $download->id,
                    'part' => $part,
                    'content' => base64_encode(substr($pdf, $part * self::PART_BYTES, self::PART_BYTES)),
                ]);
            }
        });

        return $download;
    }
}
