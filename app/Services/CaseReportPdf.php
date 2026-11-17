<?php

namespace App\Services;

use setasign\Fpdi\Fpdi;

class CaseReportPdf extends Fpdi
{
    public function __construct(private string $caseNumber)
    {
        parent::__construct();
        $this->SetAutoPageBreak(false);
        $this->AliasNbPages();
        $this->SetTitle('Background Verification Report - '.$caseNumber);
        $this->SetAuthor('Orbit Verify');
    }

    public function Footer(): void
    {
        $this->SetY(-10);
        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(100, 116, 139);
        $this->Cell(0, 4, 'Orbit Verify | Confidential | '.$this->caseNumber.' | Page '.$this->PageNo().'/{nb}', 0, 0, 'C');
    }

    public function evidenceHeader(array $evidence, int $page, int $count): void
    {
        $width = $this->GetPageWidth() - 24;
        $this->SetXY(12, 8);
        $this->SetFont('Helvetica', 'B', 9);
        $this->SetTextColor(13, 148, 136);
        $this->Cell($width, 5, $this->label($evidence['check'], $width));
        $this->SetXY(12, 14);
        $this->SetFont('Helvetica', 'B', 10);
        $this->SetTextColor(15, 32, 68);
        $this->Cell($width, 5, $this->label($evidence['name'], $width));
        $this->SetXY(12, 20);
        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(100, 116, 139);
        $this->Cell($width, 4, 'Uploaded by '.$evidence['uploader'].' | Supporting evidence | Document page '.$page.' of '.$count);
        $this->SetDrawColor(203, 213, 225);
        $this->Line(12, 27, $this->GetPageWidth() - 12, 27);
    }

    private function label(string $text, float $width): string
    {
        $text = preg_replace('/[\x00-\x1F\x7F]/', ' ', $text);
        $text = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text) ?: 'Document';
        if ($this->GetStringWidth($text) <= $width) {
            return $text;
        }
        while ($text !== '' && $this->GetStringWidth($text.'...') > $width) {
            $text = substr($text, 0, -1);
        }

        return $text.'...';
    }
}
