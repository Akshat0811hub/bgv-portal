<?php

namespace App\Concerns;

use App\Models\CaseCheck;
use App\Models\Cases;
use App\Models\Check;
use App\Models\Document;
use App\Exceptions\ReportDocumentException;
use App\Services\CaseReportBuilder;
use App\Services\ReportDownload;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
trait StoresCaseDocuments
{
    /**
     * Capture schema-driven submission (structured data + files) for every
     * check on a case from the request, and bump touched checks into review.
     * Used by both the candidate upload flow and admin "enter details now".
     *
     * @return int number of files stored
     */
    protected function captureCheckSubmission(Request $request, Cases $case, string $role): int
    {
        $uploadedFiles = 0;

        foreach ($case->checks as $check) {
            $slug = $check->check_type;
            $touched = false;

            $submitted = $request->input("check_data.$slug", []);
            if (is_array($submitted) && ! empty($submitted)) {
                $submitted = $this->pruneEmpty($submitted);
                if (! empty($submitted)) {
                    $check->form_data = array_replace_recursive($check->form_data ?? [], $submitted);
                    $touched = true;
                }
            }

            foreach ((array) $request->file("check_files.$slug", []) as $fieldKey => $files) {
                foreach ((array) $files as $file) {
                    if ($file instanceof UploadedFile && $file->isValid()) {
                        $this->storeDocument($file, $case, $check, (string) $fieldKey, $role);
                        $uploadedFiles++;
                        $touched = true;
                    }
                }
            }

            if ($touched && in_array($check->status, ['Pending', 'Insufficiency'])) {
                $check->status = 'In Progress';
            }

            if ($check->isDirty()) {
                $check->save();
            }
        }

        return $uploadedFiles;
    }

    /**
     * Generate and stream the BGV PDF report for a case.
     */
    protected function caseReportPdf(Cases $case)
    {
        try {
            $pdf = app(CaseReportBuilder::class)->build($case);
            return app(ReportDownload::class)->response($case, $pdf);
        } catch (ReportDocumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    /**
     * Store an uploaded file on the private disk and create its Document record.
     */
    protected function storeDocument(
        UploadedFile $file,
        Cases $case,
        ?CaseCheck $check,
        string $fieldKey,
        string $role,
    ): Document {
        $rawContent = null;
        try {
            if ($file->getRealPath() && is_readable($file->getRealPath())) {
                $rawContent = base64_encode((string) file_get_contents($file->getRealPath()));
            }
        } catch (\Throwable $e) {
            $rawContent = null;
        }

        $mimeType = $file->getMimeType() ?: ($file->getClientMimeType() ?: 'application/octet-stream');
        $fileSize = $file->getSize() ?: 0;

        $path = $file->store("cases/{$case->id}/{$fieldKey}", 'local');
        if (! $path) {
            $ext = $file->getClientOriginalExtension() ?: 'bin';
            $path = "cases/{$case->id}/{$fieldKey}/" . Str::random(40) . '.' . $ext;
        }

        $rawName = $file->getClientOriginalName();
        $safeName = preg_replace('/[\x00-\x1F\x7F\/\\\\<>:"\'|?*]/', '_', basename($rawName));
        $safeName = mb_substr($safeName, 0, 200) ?: 'document';

        $payload = [
            'case_id' => $case->id,
            'case_check_id' => $check?->id,
            'uploaded_by' => $role === 'candidate' ? null : auth()->id(),
            'original_name' => $safeName,
            'stored_path' => $path,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'field_key' => $fieldKey,
            'uploaded_by_role' => $role,
        ];

        if (\Illuminate\Support\Facades\Schema::hasColumn('documents', 'file_content')) {
            $payload['file_content'] = $rawContent;
        }

        try {
            return Document::create($payload);
        } catch (\Throwable $e) {
            // If MySQL max_allowed_packet rejects large file_content, fall back to disk-only storage.
            if (isset($payload['file_content']) && \Illuminate\Support\Facades\Storage::disk('local')->exists($path)) {
                $payload['file_content'] = null;

                return Document::create($payload);
            }
            throw $e;
        }
    }

    /**
     * Recursively drop empty strings / nulls so blanks don't overwrite saved data.
     */
    protected function pruneEmpty(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->pruneEmpty($value);
                if (empty($data[$key])) {
                    unset($data[$key]);
                }
            } elseif ($value === null || $value === '') {
                unset($data[$key]);
            }
        }

        return $data;
    }
}
