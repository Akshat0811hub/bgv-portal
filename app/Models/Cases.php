<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cases extends Model
{
    protected $table = 'cases';

    protected $fillable = [
        'case_number', 'client_organization_id', 'created_by', 'status',
        'candidate_name', 'candidate_email', 'candidate_phone',
        'candidate_father_name', 'candidate_designation',
        'case_start_date', 'report_closed_date', 'admin_notes',
    ];

    protected $casts = [
        'case_start_date'    => 'date',
        'report_closed_date' => 'date',
    ];

    public function clientOrganization()
    {
        return $this->belongsTo(ClientOrganization::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function checks()
    {
        return $this->hasMany(CaseCheck::class, 'case_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'case_id');
    }

    public function uploadToken()
    {
        return $this->hasOne(UploadToken::class, 'case_id');
    }

    public function recalculateStatus(): string
    {
        $statuses = $this->checks()->pluck('status')->toArray();

        if (empty($statuses)) {
            return 'Pending';
        }
        if (in_array('On Hold', $statuses))           return 'On Hold';
        if (in_array('Insufficiency', $statuses))     return 'Insufficiency';
        if (in_array('Pending', $statuses))           return 'Pending';
        if (in_array('In Progress', $statuses))       return 'In Progress';
        if (in_array('Amber', $statuses))             return 'Amber';
        if (in_array('Discrepancy Found', $statuses)) return 'Completed - Discrepancy Found';

        return 'Completed - Green (Clear)';
    }

    public static function generateCaseNumber(): string
    {
        $prefix = 'OV-' . date('Y') . '-';
        $last = self::where('case_number', 'like', $prefix . '%')->max('case_number');
        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;
        return $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}
