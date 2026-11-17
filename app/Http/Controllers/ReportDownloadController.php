<?php

namespace App\Http\Controllers;

use App\Models\Cases;
use Illuminate\Support\Facades\DB;

class ReportDownloadController extends Controller
{
    public function part(string $download, int $part)
    {
        $record = DB::table('report_downloads')->where('id', $download)->first();
        abort_unless($record && (int) $record->user_id === (int) auth()->id(), 404);
        if (now()->gte($record->expires_at)) {
            DB::table('report_downloads')->where('id', $download)->delete();
            abort(410, 'This download has expired. Generate the report again.');
        }

        $case = Cases::findOrFail($record->case_id);
        $user = auth()->user();
        abort_unless($user->isAdmin() || ($user->isClient() && (int) $case->client_organization_id === (int) $user->client_organization_id), 403);
        abort_if($part < 0 || $part >= $record->parts, 404);

        $encoded = DB::table('report_download_parts')->where('report_download_id', $download)
            ->where('part', $part)->value('content');
        abort_unless(is_string($encoded), 404);
        $bytes = base64_decode($encoded, true);
        abort_if($bytes === false, 500, 'This report could not be read. Generate it again.');

        return response($bytes, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => strlen($bytes),
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
