<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function view(Document $document)
    {
        $user = auth()->user();
        // Admin sees all; clients see only their organization's case documents
        if (! $user->isAdmin() && $document->case->client_organization_id !== $user->client_organization_id) {
            abort(403);
        }

        $disk = Storage::disk('local');
        if (! $disk->exists($document->stored_path)) {
            abort(404);
        }

        $path = $disk->path($document->stored_path);

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

    public function destroy(Document $document)
    {
        $user = auth()->user();
        // Admin can delete any document; client can only delete documents they uploaded while the case is not completed
        $canClientDelete = $user->isClient()
            && $document->case->client_organization_id === $user->client_organization_id
            && $document->uploaded_by_role === 'client'
            && ! $document->case->isCompletedStatus();

        if (! $user->isAdmin() && ! $canClientDelete) {
            abort(403);
        }

        $disk = Storage::disk('local');
        if ($disk->exists($document->stored_path)) {
            $disk->delete($document->stored_path);
        }

        $document->delete();

        return back()->with('success', 'Document removed successfully.');
    }
}
