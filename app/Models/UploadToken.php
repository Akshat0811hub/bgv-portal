<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class UploadToken extends Model
{
    protected $fillable = ['case_id', 'token', 'used', 'expires_at'];

    protected $casts = [
        'used' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function case()
    {
        return $this->belongsTo(Cases::class, 'case_id');
    }

    public function isValid(): bool
    {
        return ! $this->used && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public static function encodeSnapshot(Cases $case): string
    {
        $case->loadMissing('checks', 'clientOrganization');
        $customSchemas = [];
        $customNames = [];
        foreach ($case->checks as $chk) {
            if (is_array($chk->custom_schema)) {
                $customSchemas[$chk->check_type] = $chk->custom_schema;
            }
            if ($chk->custom_name) {
                $customNames[$chk->check_type] = $chk->custom_name;
            }
        }

        $data = [
            'cn' => $case->case_number,
            'na' => $case->candidate_name,
            'em' => $case->candidate_email,
            'ph' => $case->candidate_phone,
            'fn' => $case->candidate_father_name,
            'de' => $case->candidate_designation,
            'or' => $case->clientOrganization?->name ?? 'Orbit Verify Client',
            'ch' => $case->checks->pluck('check_type')->values()->all(),
            'cs' => $customSchemas,
            'cm' => $customNames,
        ];

        return rtrim(strtr(base64_encode((string) json_encode($data, JSON_UNESCAPED_UNICODE)), '+/', '-_'), '=');
    }

    public static function resolveOrHeal(string $token, ?Request $request = null): self
    {
        $existing = self::where('token', $token)->with('case.checks')->first();
        if ($existing && $existing->case) {
            return $existing;
        }

        // A permanent database is the authority for candidate access.
        // Never create or replace a token from an unsigned URL snapshot.
        abort(404, 'This verification link is invalid or no longer available.');
    }
}