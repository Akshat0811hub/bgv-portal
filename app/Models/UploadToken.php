<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class UploadToken extends Model
{
    protected $fillable = ['case_id', 'token', 'used', 'expires_at'];

    protected $casts = [
        'used'       => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function case()
    {
        return $this->belongsTo(Cases::class, 'case_id');
    }

    public function isValid(): bool
    {
        return !$this->used && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public static function encodeSnapshot(Cases $case): string
    {
        $case->loadMissing('checks', 'clientOrganization');
        $data = [
            'cn' => $case->case_number,
            'na' => $case->candidate_name,
            'em' => $case->candidate_email,
            'ph' => $case->candidate_phone,
            'fn' => $case->candidate_father_name,
            'de' => $case->candidate_designation,
            'or' => $case->clientOrganization?->name ?? 'Orbit Verify Client',
            'ch' => $case->checks->pluck('check_type')->values()->all(),
        ];

        return rtrim(strtr(base64_encode((string) json_encode($data, JSON_UNESCAPED_UNICODE)), '+/', '-_'), '=');
    }

    public static function resolveOrHeal(string $token, ?Request $request = null): self
    {
        $existing = self::where('token', $token)->with('case.checks')->first();
        if ($existing && $existing->case) {
            return $existing;
        }

        // Reconstruct case & token if an ephemeral serverless SQLite instance was cold-started
        $snapRaw = $request?->input('_case_snapshot') ?: $request?->query('ctx');
        $snap = [];
        if (is_string($snapRaw) && $snapRaw !== '') {
            $decoded = base64_decode(strtr($snapRaw, '-_', '+/'), true);
            if ($decoded !== false) {
                $parsed = json_decode($decoded, true);
                if (is_array($parsed)) {
                    $snap = $parsed;
                }
            }
        }

        $submittedCheckSlugs = array_values(array_unique(array_filter(array_merge(
            array_keys((array) $request?->input('check_data', [])),
            array_keys((array) ($request?->allFiles()['check_files'] ?? [])),
            (array) ($snap['ch'] ?? [])
        ), fn ($s) => is_string($s) && $s !== '')));

        if (empty($submittedCheckSlugs)) {
            $submittedCheckSlugs = ['national_id_verification', 'address_verification', 'education_verification'];
        }

        $orgName = $snap['or'] ?? 'Orbit Verify Client';
        $org = ClientOrganization::firstOrCreate(['name' => $orgName]);
        $creator = User::first();

        $caseNumber = $snap['cn'] ?? ('OV-' . date('Y') . '-' . strtoupper(substr(md5($token), 0, 5)));
        $case = Cases::firstOrCreate(
            ['case_number' => $caseNumber],
            [
                'client_organization_id' => $org->id,
                'created_by'             => $creator?->id,
                'status'                 => 'Pending',
                'candidate_name'         => $snap['na'] ?? 'Candidate',
                'candidate_email'        => $snap['em'] ?? 'candidate@orbitverify.com',
                'candidate_phone'        => $snap['ph'] ?? null,
                'candidate_father_name'  => $snap['fn'] ?? null,
                'candidate_designation'  => $snap['de'] ?? null,
                'case_start_date'        => now()->toDateString(),
            ]
        );

        foreach ($submittedCheckSlugs as $slug) {
            $case->checks()->firstOrCreate(
                ['check_type' => $slug],
                ['status' => 'Pending']
            );
        }

        $uploadToken = self::updateOrCreate(
            ['case_id' => $case->id],
            [
                'token'      => substr($token, 0, 64),
                'used'       => false,
                'expires_at' => now()->addDays(7),
            ]
        );

        return $uploadToken->load('case.checks');
    }
}
