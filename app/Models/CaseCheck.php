<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseCheck extends Model
{
    protected $fillable = [
        'case_id', 'check_type', 'status', 'form_data', 'verified_data', 'comments', 'completed_at',
    ];

    protected $casts = [
        'form_data'     => 'array',
        'verified_data' => 'array',
        'completed_at'  => 'datetime',
    ];

    public function case()
    {
        return $this->belongsTo(Cases::class, 'case_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'case_check_id');
    }

    public function getStatusColorClass(): string
    {
        return match($this->status) {
            'Pending'          => 'badge-pending',
            'In Progress'      => 'badge-progress',
            'Clear'            => 'badge-clear',
            'Amber'            => 'badge-amber',
            'Discrepancy Found'=> 'badge-discrepancy',
            'Insufficiency'    => 'badge-insufficiency',
            'Failed'           => 'badge-failed',
            'Completed'        => 'badge-completed',
            default            => 'badge-pending',
        };
    }
}
