<?php

namespace App\Services;

use App\Models\Cases;
use App\Models\Check;
use App\Models\Document;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfReader\PageBoundaries;

class CaseReportBuilder
{
    public function __construct(private ReportDocumentConverter $converter) {}

    public function build(Cases $case): string
    {
        $case->load('checks', 'clientOrganization');
        $case->setRelation('checks', $case->checks->sortBy('id')->values());
        $schemas = Check::whereIn('slug', $case->checks->pluck('check_type'))->get()
            ->mapWithKeys(fn ($check) => [$check->slug => $check->schema ?? []]);
        $documents = $case->documents()->orderBy('id')->get();
        foreach ($case->checks as $check) {
            $check->setRelation('documents', $documents->where('case_check_id', $check->id)->values());
        }
        $merged = new CaseReportPdf($case->case_number);
        $this->appendPdf($merged, $this->renderPdf('reports.case', compact('case', 'documents')));
        foreach ($case->checks as $index => $check) {
            $schema = $check->effectiveSchema($schemas[$check->check_type] ?? []);
            $number = $index + 1;
            $documentResults = $check->documents->mapWithKeys(fn ($document) => [$document->id => $this->prepareDocument($document)]);
            $this->appendPdf($merged, $this->renderPdf('reports.check', compact('case', 'check', 'schema', 'number', 'documentResults')));
            foreach ($check->documents as $document) {
                $this->appendDocument($merged, $document, $documentResults[$document->id], ucwords(str_replace('_', ' ', $check->check_type)));
            }
        }
        $checkIds = $case->checks->pluck('id')->all();
        $generalDocuments = $documents->reject(fn ($doc) => in_array($doc->case_check_id, $checkIds, true));
        if ($generalDocuments->isNotEmpty()) {
            $documentResults = $generalDocuments->mapWithKeys(fn ($document) => [$document->id => $this->prepareDocument($document)]);
            $this->appendPdf($merged, $this->renderPdf('reports.general-documents', [
                'case' => $case, 'documents' => $generalDocuments, 'documentResults' => $documentResults,
            ]));
            foreach ($generalDocuments as $document) {
                $this->appendDocument($merged, $document, $documentResults[$document->id], 'General case documents');
            }
        }

        return $merged->Output('S');
    }

    private function prepareDocument(Document $document): array
    {
        $originalAvailable = false;
        try {
            $bytes = $this->readDocument($document);
            $originalAvailable = true;
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
            $extension = strtolower(pathinfo($document->original_name, PATHINFO_EXTENSION));
            if ($mime === 'application/pdf' || $extension === 'pdf') {
                try {
                    $this->validatePdf($bytes);
                } catch (\Throwable) {
                    $bytes = $this->converter->normalizePdf($bytes);
                }
            } elseif (in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                $bytes = $this->imagePdf($bytes);
            } elseif (in_array($extension, ['txt', 'csv'], true)) {
                $bytes = $this->textPdf($bytes, $document->original_name);
            } else {
                $bytes = $this->converter->convert($bytes, $document->original_name);
            }
            $this->validatePdf($bytes);

            return ['pdf' => $bytes, 'status' => 'embedded'];
        } catch (\Throwable $exception) {
            Log::warning('Report document preview unavailable', ['document_id' => $document->id, 'reason' => $exception->getMessage()]);

            return ['pdf' => null, 'status' => $originalAvailable ? 'linked' : 'missing'];
        }
    }

    private function appendDocument(CaseReportPdf $merged, Document $document, array $result, string $checkName): void
    {
        if ($result['pdf'] !== null) {
            $this->appendPdf($merged, $result['pdf'], [
                'check' => $checkName, 'name' => $document->original_name,
                'uploader' => ucfirst($document->uploaded_by_role ?: 'Unknown'),
            ]);
        }
    }

    private function renderPdf(string $view, array $data): string
    {
        return Pdf::loadView($view, $data)->setPaper('a4')->setOption([
            'enable_font_subsetting' => true, 'isRemoteEnabled' => false,
        ])->output();
    }

    private function readDocument(Document $document): string
    {
        $disk = Storage::disk('local');
        if ($document->stored_path && $disk->exists($document->stored_path)) {
            $bytes = $disk->get($document->stored_path);
            if (is_string($bytes) && $bytes !== '') {
                return $bytes;
            }
        }
        $bytes = base64_decode($document->file_content ?? '', true);
        if ($bytes === false || $bytes === '') {
            throw new \RuntimeException('Document content is missing.');
        }

        return $bytes;
    }

    private function validatePdf(string $bytes): void
    {
        $probe = new Fpdi;
        $count = $probe->setSourceFile(StreamReader::createByString($bytes));
        if ($count < 1) {
            throw new \RuntimeException('PDF contains no pages.');
        }
        for ($page = 1; $page <= $count; $page++) {
            $probe->importPage($page);
        }
    }

    private function appendPdf(CaseReportPdf $merged, string $bytes, ?array $evidence = null): void
    {
        $count = $merged->setSourceFile(StreamReader::createByString($bytes));
        for ($page = 1; $page <= $count; $page++) {
            // Keep clickable original-file links in generated report sections.
            $template = $merged->importPage($page, PageBoundaries::CROP_BOX, true, $evidence === null);
            $size = $merged->getTemplateSize($template);
            if ($evidence === null) {
                $merged->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $merged->useTemplate($template);

                continue;
            }
            $merged->AddPage($size['orientation'], 'A4');
            $merged->evidenceHeader($evidence, $page, $count);
            $scale = min(($merged->GetPageWidth() - 24) / $size['width'], ($merged->GetPageHeight() - 48) / $size['height']);
            $width = $size['width'] * $scale;
            $merged->useTemplate($template, ($merged->GetPageWidth() - $width) / 2, 32, $width, $size['height'] * $scale);
        }
    }

    private function imagePdf(string $bytes): string
    {
        $image = @imagecreatefromstring($bytes);
        if ($image === false) {
            throw new \RuntimeException('Unreadable image.');
        }
        try {
            $max = max(imagesx($image), imagesy($image));
            if ($max > 2400) {
                $scale = 2400 / $max;
                $resized = imagescale($image, (int) round(imagesx($image) * $scale), (int) round(imagesy($image) * $scale));
                if ($resized !== false) {
                    imagedestroy($image);
                    $image = $resized;
                }
            }
            $canvas = imagecreatetruecolor(imagesx($image), imagesy($image));
            imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
            imagecopy($canvas, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
            ob_start();
            try {
                imagejpeg($canvas, null, 90);
                $bytes = ob_get_contents();
            } finally {
                ob_end_clean();
                imagedestroy($canvas);
            }
        } finally {
            imagedestroy($image);
        }
        $imageUri = 'data:image/jpeg;base64,'.base64_encode($bytes);

        return $this->renderPdf('reports.evidence-image', compact('imageUri'));
    }

    private function textPdf(string $bytes, string $title): string
    {
        $text = mb_convert_encoding($bytes, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        $blocks = array_map(fn ($line) => ['type' => 'p', 'text' => $line], preg_split('/\r\n|\n|\r/', $text));

        return $this->renderPdf('reports.evidence-text', compact('title', 'blocks'));
    }
}
