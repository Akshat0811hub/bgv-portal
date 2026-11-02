<?php

namespace App\Http\Controllers\Candidate;

use App\Concerns\StoresCaseDocuments;
use App\Http\Controllers\Controller;
use App\Models\Check;
use App\Models\UploadToken;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    use StoresCaseDocuments;

    public function show(string $token)
    {
        $uploadToken = UploadToken::where('token', $token)->with('case.checks')->firstOrFail();

        if (! $uploadToken->isValid()) {
            return view('candidate.expired');
        }

        $schemas = $this->schemasFor($uploadToken->case->checks->pluck('check_type'));

        return view('candidate.upload', compact('uploadToken', 'schemas'));
    }

    public function store(Request $request, string $token)
    {
        $uploadToken = UploadToken::where('token', $token)->with('case.checks')->firstOrFail();

        if (! $uploadToken->isValid()) {
            abort(410, 'This upload link has expired.');
        }

        $request->validate([
            'check_data' => 'nullable|array',
            'check_files' => 'nullable|array',
            'check_files.*' => 'array',
            'check_files.*.*' => 'array|max:5',
            'check_files.*.*.*' => 'file|max:10240|mimes:pdf,jpg,jpeg,png,webp',
        ]);

        $case = $uploadToken->case;

        $this->captureCheckSubmission($request, $case, 'candidate');

        // Mark token used and recalculate the case status.
        $uploadToken->update(['used' => true]);
        $case->update(['status' => $case->recalculateStatus()]);

        // Show a dedicated thank-you page (the link is now single-use, so we must
        // NOT redirect back to it — that would render the "expired" page).
        return view('candidate.success', compact('case'));
    }

    /**
     * Build a slug => schema(array) map for the given check slugs.
     */
    private function schemasFor($slugs)
    {
        return Check::whereIn('slug', $slugs)->get()
            ->mapWithKeys(fn ($c) => [$c->slug => $c->schema ?? []]);
    }
}
