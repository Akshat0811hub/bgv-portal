<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
