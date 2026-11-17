<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    public function isCompletedStatus(?string $status = null): bool
    {
        $s = $status ?? $this->status;
        return str_starts_with((string) $s, 'Completed');
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
        if (in_array('Discrepancy Found', $statuses) || in_array('Failed', $statuses)) {
            return 'Completed - Discrepancy Found';
        }

        return 'Completed - Green (Clear)';
    }

    public function syncStatusAndCloseDate(?string $explicitStatus = null): void
    {
        $wasCompleted = $this->isCompletedStatus($this->status);
        $newStatus    = $explicitStatus ?? $this->recalculateStatus();
        $nowCompleted = $this->isCompletedStatus($newStatus);

        $updates = ['status' => $newStatus];

        if ($nowCompleted) {
            if (! $this->report_closed_date) {
                $updates['report_closed_date'] = today();
            }
        } else {
            $updates['report_closed_date'] = null;
        }

        $this->update($updates);

        // Notify the initiating client user when a case transitions to Completed
        if (! $wasCompleted && $nowCompleted) {
            $this->loadMissing('createdBy');
            $recipient = $this->createdBy?->email;
            if ($recipient) {
                try {
                    \Illuminate\Support\Facades\Mail::to($recipient)->send(new \App\Mail\CaseCompletedMail($this));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Failed to send case completion email: ' . $e->getMessage());
                }
            }
        }
    }

    public static function generateCaseNumber(): string
    {
        return DB::transaction(function () {
            $prefix = 'OV-' . date('Y') . '-';
            $last = self::where('case_number', 'like', $prefix . '%')
                ->orderByDesc('case_number')
                ->lockForUpdate()
                ->value('case_number');
            $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;
            return $prefix . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
        });
    }
}
