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
            'check_files.*' => 'nullable|array',
            'check_files.*.*' => 'nullable',
            'check_files.*.*.*' => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png,webp',
        ]);

        $case = $uploadToken->case;
        $schemas = $this->schemasFor($case->checks->pluck('check_type'));

        // Check whether the candidate actually entered any new data or uploaded any files
        $rawCheckData = $this->pruneEmpty((array) $request->input('check_data', []));
        $uploadedFilesByCheck = (array) ($request->allFiles()['check_files'] ?? []);
        $hasNewFiles = ! empty($uploadedFilesByCheck);

        // Enforce that checks flagged for Insufficiency have their supporting documents attached
        foreach ($case->checks as $chk) {
            $slug = $chk->check_type;
            if ($chk->status === 'Insufficiency') {
                $alreadyHasDocs = $chk->documents()->count() > 0;
                $uploadedForThisCheck = ! empty($uploadedFilesByCheck[$slug] ?? []);

                if (! $alreadyHasDocs && ! $uploadedForThisCheck) {
                    $checkLabel = ucwords(str_replace('_', ' ', $slug));
                    return back()
                        ->withErrors(['upload' => "Please upload the required supporting document(s) for {$checkLabel} before submitting."])
                        ->withInput();
                }
            }
        }

        // Detect if candidate changed any field vs pre-filled form_data
        $changedAnyField = false;
        foreach ($case->checks as $chk) {
            $slug = $chk->check_type;
            $existing = $this->pruneEmpty((array) ($chk->form_data ?? []));
            $incoming = $rawCheckData[$slug] ?? [];
            if (! empty($incoming) && $incoming != $existing) {
                $changedAnyField = true;
                break;
            }
        }

        if (! $hasNewFiles && ! $changedAnyField) {
            return back()
                ->withErrors(['upload' => 'Please upload at least one required document or fill in your verification details before submitting.'])
                ->withInput();
        }

        $this->captureCheckSubmission($request, $case, 'candidate');

        // Mark token used and recalculate the case status.
        $uploadToken->update(['used' => true]);
        $case->syncStatusAndCloseDate();

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
