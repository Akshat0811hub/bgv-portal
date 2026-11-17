<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseCheck extends Model
{
    protected $fillable = [
        'case_id', 'check_type', 'status', 'form_data', 'verified_data', 'comments', 'custom_schema', 'completed_at',
    ];

    protected $casts = [
        'form_data'     => 'array',
        'verified_data' => 'array',
        'custom_schema' => 'array',
        'completed_at'  => 'datetime',
    ];

    public function effectiveSchema(?array $globalSchema = null): array
    {
        if (is_array($this->custom_schema)) {
            return $this->custom_schema;
        }

        if ($globalSchema !== null) {
            return $globalSchema;
        }

        return Check::where('slug', $this->check_type)->value('schema') ?? [];
    }

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
