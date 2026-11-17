<?php

namespace App\Services;

use App\Models\Cases;
use App\Models\Check;
use App\Models\Document;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

class CaseReportBuilder
{
    private const VERCEL_MAX_PAYLOAD_BYTES = 4_200_000;

    public function build(Cases $case): string
    {
        $case->loadMissing('checks.documents', 'clientOrganization');
        $schemas = Check::whereIn('slug', $case->checks->pluck('check_type'))->get()
            ->mapWithKeys(fn ($check) => [$check->slug => $check->schema ?? []]);

        // Include every document belonging to this case, once, in a stable order.
        $documents = $case->documents()->with('caseCheck')->orderBy('case_check_id')->orderBy('id')->get();
        $appendices = $documents->values()->mapWithKeys(fn ($doc, $index) => [$doc->id => $index + 1]);
        $report = $this->renderPdf('reports.case', compact('case', 'schemas', 'documents', 'appendices'));

        if ($documents->isEmpty()) {
            return $report;
        }

        $merged = new Fpdi();
        $merged->SetAutoPageBreak(false);
        $merged->SetTitle('Background Verification Report - '.$case->case_number);
        $merged->SetAuthor('Orbit Verify');
        $this->appendPdf($merged, $report);

        $runningBytes = strlen($report);

        foreach ($documents as $document) {
            $divider = $this->renderPdf('reports.appendix', [
                'case' => $case,
                'document' => $document,
                'number' => $appendices[$document->id],
            ]);
            $this->appendPdf($merged, $divider);
            $runningBytes += strlen($divider);

            try {
                $bytes = $this->readDocument($document);
                $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
                $ext = strtolower(pathinfo($document->original_name ?? '', PATHINFO_EXTENSION));

                if ($mime === 'application/pdf' || $ext === 'pdf') {
                    $piece = $bytes;
                } elseif (in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                    $piece = $this->imagePdf($bytes, $mime);
                } elseif ($ext === 'docx' || $mime === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                    $piece = $this->docxPdf($bytes, $document->original_name);
                } elseif (str_starts_with((string) $mime, 'text/') || in_array($ext, ['txt', 'csv', 'log'], true)) {
                    $piece = $this->textPdf($bytes, $document->original_name);
                } else {
                    $piece = $this->fallbackPdf($document);
                }

                // Stay safely below Vercel's 4.5 MB serverless response payload ceiling.
                if (($runningBytes + strlen($piece)) > self::VERCEL_MAX_PAYLOAD_BYTES && (getenv('VERCEL') || getenv('VERCEL_ENV'))) {
                    $piece = $this->fallbackPdf($document);
                }

                $this->appendPdf($merged, $piece);
                $runningBytes += strlen($piece);
            } catch (\Throwable $exception) {
                $fallback = $this->fallbackPdf($document);
                $this->appendPdf($merged, $fallback);
                $runningBytes += strlen($fallback);
            }
        }

        $output = $merged->Output('S');
        if (strlen($output) > self::VERCEL_MAX_PAYLOAD_BYTES && (getenv('VERCEL') || getenv('VERCEL_ENV'))) {
            return $report;
        }

        return $output;
    }

    private function renderPdf(string $view, array $data, string $paper = 'a4'): string
    {
        return Pdf::loadView($view, $data)
            ->setPaper($paper)
            ->setOption([
                'enable_font_subsetting' => true,
                'isRemoteEnabled' => false,
            ])
            ->output();
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

    private function appendPdf(Fpdi $merged, string $bytes): void
    {
        $count = $merged->setSourceFile(StreamReader::createByString($bytes));
        if ($count < 1) {
            throw new \RuntimeException('Document contains no pages.');
        }
        for ($page = 1; $page <= $count; $page++) {
            $template = $merged->importPage($page);
            $size = $merged->getTemplateSize($template);
            $merged->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $merged->useTemplate($template);
        }
    }

    private function imagePdf(string $bytes, string $mime): string
    {
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new \RuntimeException('Unsupported document format.');
        }

        // Downscale & compress large images (e.g. 5MB phone photos) so merged PDF stays lightweight.
        if (function_exists('imagecreatefromstring')) {
            $image = @imagecreatefromstring($bytes);
            if ($image === false) {
                throw new \RuntimeException('Unreadable image.');
            }
            try {
                $width = imagesx($image);
                $height = imagesy($image);
                if ($width > 1400) {
                    $scaled = imagescale($image, 1400);
                    if ($scaled !== false) {
                        imagedestroy($image);
                        $image = $scaled;
                    }
                }
                if ($mime === 'image/webp' || strlen($bytes) > 350_000) {
                    ob_start();
                    imagejpeg($image, null, 78);
                    $compressed = ob_get_clean();
                    if (is_string($compressed) && $compressed !== '') {
                        $bytes = $compressed;
                        $mime = 'image/jpeg';
                    }
                }
            } finally {
                imagedestroy($image);
            }
        }

        if (@getimagesizefromstring($bytes) === false) {
            throw new \RuntimeException('Unreadable image.');
        }
        $imageUri = 'data:'.$mime.';base64,'.base64_encode($bytes);

        return $this->renderPdf('reports.evidence-image', compact('imageUri'));
    }

    private function docxPdf(string $bytes, string $title): string
    {
        if (! class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('ZipArchive unavailable.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'docx_');
        if ($tmp === false) {
            throw new \RuntimeException('Unable to create temp file.');
        }
        file_put_contents($tmp, $bytes);

        $blocks = [];
        try {
            $zip = new \ZipArchive();
            if ($zip->open($tmp) !== true) {
                throw new \RuntimeException('Unable to open DOCX archive.');
            }
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();

            if (! is_string($xml) || $xml === '') {
                throw new \RuntimeException('Missing word/document.xml.');
            }

            $dom = new \DOMDocument();
            $dom->loadXML($xml, LIBXML_NOERROR | LIBXML_NOWARNING);
            $xpath = new \DOMXPath($dom);
            $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

            foreach ($xpath->query('//w:body/*') as $node) {
                if ($node->localName === 'p') {
                    $text = '';
                    foreach ($xpath->query('.//w:t', $node) as $t) {
                        $text .= $t->nodeValue;
                    }
                    $text = trim($text);
                    if ($text !== '') {
                        $blocks[] = ['type' => 'p', 'text' => $text];
                    }
                } elseif ($node->localName === 'tbl') {
                    $rows = [];
                    foreach ($xpath->query('./w:tr', $node) as $tr) {
                        $row = [];
                        foreach ($xpath->query('./w:tc', $tr) as $tc) {
                            $cellText = [];
                            foreach ($xpath->query('.//w:p', $tc) as $cp) {
                                $pt = '';
                                foreach ($xpath->query('.//w:t', $cp) as $t) {
                                    $pt .= $t->nodeValue;
                                }
                                if (trim($pt) !== '') {
                                    $cellText[] = trim($pt);
                                }
                            }
                            $row[] = implode("\n", $cellText);
                        }
                        if (! empty($row)) {
                            $rows[] = $row;
                        }
                    }
                    if (! empty($rows)) {
                        $blocks[] = ['type' => 'table', 'rows' => $rows];
                    }
                }
            }
        } finally {
            @unlink($tmp);
        }

        return $this->renderPdf('reports.evidence-text', compact('title', 'blocks'));
    }

    private function textPdf(string $bytes, string $title): string
    {
        $text = mb_convert_encoding($bytes, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        $blocks = [];
        foreach (preg_split("/\r\n|\n|\r/", $text) as $line) {
            if (trim($line) !== '') {
                $blocks[] = ['type' => 'p', 'text' => $line];
            }
        }

        return $this->renderPdf('reports.evidence-text', compact('title', 'blocks'));
    }

    private function fallbackPdf(Document $document): string
    {
        $viewUrl = route('documents.view', $document);

        return $this->renderPdf('reports.evidence-fallback', compact('document', 'viewUrl'));
    }
}
