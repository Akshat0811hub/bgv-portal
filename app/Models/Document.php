<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'case_id', 'case_check_id', 'uploaded_by', 'original_name',
        'stored_path', 'mime_type', 'file_size', 'field_key', 'uploaded_by_role',
    ];

    public function case()
    {
        return $this->belongsTo(Cases::class, 'case_id');
    }

    public function caseCheck()
    {
        return $this->belongsTo(CaseCheck::class, 'case_check_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
