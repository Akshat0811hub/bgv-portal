<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function view(\App\Models\Document $document)
    {
        $user = auth()->user();
        // Admin sees all; clients see only their own case documents
        if (!$user->isAdmin() && $document->case->created_by !== $user->id) {
            abort(403);
        }

        $path = storage_path('app/private/' . $document->stored_path);

        if (!file_exists($path)) abort(404);

        // Strip any characters that could break the Content-Disposition header
        $safeName = preg_replace('/[\x00-\x1F\x7F"\\\\]/', '_', $document->original_name);
        $safeMime = in_array($document->mime_type, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
            ? $document->mime_type
            : 'application/octet-stream';

        return response()->file($path, [
            'Content-Type'        => $safeMime,
            'Content-Disposition' => 'inline; filename="' . $safeName . '"',
        ]);
    }
}
