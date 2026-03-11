<?php

namespace App\Services;

// Ensure TCPDF (tecnickcom/tcpdf) is loaded. Class name is TCPDF, not ICPDF.
if (! class_exists('\\TCPDF', false)) {
    $tcpdfPath = base_path('vendor/tecnickcom/tcpdf/tcpdf.php');
    if (is_file($tcpdfPath)) {
        require_once $tcpdfPath;
    }
}

/**
 * TCPDF subclass for WFH Task List / Accomplishment Report PDF export.
 *
 * Layout, header, footer, margins, and table styling refer to:
 *   app/Models/generate_pdf.php (lines 1–161)
 * That file defines the intended design: landscape A4, centered header logo,
 * "WORK FROM HOME INDIVIDUAL ACCOMPLISHMENT REPORT" title, custom subtitle,
 * footer image and "© DepEd | Page X/Y", and the same table CSS.
 * This class implements that design within the Laravel application.
 *
 * Extends global TCPDF from tecnickcom/tcpdf (no namespace).
 */
class WfhTaskListPdf extends \TCPDF
{
    protected string $reportSubtitle;

    public function __construct(
        string $orientation = 'L',
        string $unit = 'in',
        string $format = 'A4',
        bool $unicode = true,
        string $encoding = 'UTF-8',
        bool $diskcache = false,
        string $reportSubtitle = 'Date Range Not Specified'
    ) {
        $this->reportSubtitle = $reportSubtitle;
        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache);
    }

    public function Header(): void
    {
        // Use header image from Accomplishment Report Templates (matches DepEd template: seal, Republic, DepEd, Region, Division, line)
        $headerFile = public_path('Accomplishment Report Templates/header.jpg');
        if (! is_file($headerFile)) {
            $headerFile = public_path('assets/img/header.png');
        }
        $imageWidth = 11;
        $pageWidth = 11.5;
        $leftMargin = 1.5;
        $rightMargin = 0.6;
        $printableWidth = $pageWidth - $leftMargin - $rightMargin;
        $xCenter = (($printableWidth - $imageWidth) / 2) + $leftMargin;

        if (is_file($headerFile)) {
            $type = strtolower(pathinfo($headerFile, PATHINFO_EXTENSION)) === 'jpg' ? 'JPEG' : 'PNG';
            $this->Image($headerFile, $xCenter, 0.5, $imageWidth, 2.0, $type, '', 'T', false, 300, '', false, false, 0, false, false, true, false, false);
        }

        $this->SetFont('helvetica', 'B', 10);
        $this->MultiCell(7.0, 0.2, 'WORK FROM HOME INDIVIDUAL ACCOMPLISHMENT REPORT', 0, 'C', 0, 0.5, 2.4, 2.4, true);

        $this->SetFont('helvetica', '', 10);
        $this->MultiCell(6.8, 0.2, $this->reportSubtitle, 0, 'C', 0, 0.5, 2.4, 2.6, true);

        $this->Line(3.4, 2.6, 8.5, 2.6);
    }

    public function Footer(): void
    {
        // Use footer image from Accomplishment Report Templates (matches DepEd template: logos, contact info, doc ref)
        $footerImageFile = public_path('Accomplishment Report Templates/footer.jpg');
        if (! is_file($footerImageFile)) {
            $footerImageFile = public_path('assets/img/footer.png');
        }
        $pageWidth = $this->getPageWidth();
        $footerHeight = 1;
        $yPos = $this->getPageHeight() - $footerHeight - 0.3;

        if (is_file($footerImageFile)) {
            $type = strtolower(pathinfo($footerImageFile, PATHINFO_EXTENSION)) === 'jpg' ? 'JPEG' : 'PNG';
            $this->Image(
                $footerImageFile,
                0,
                $yPos,
                $pageWidth,
                $footerHeight,
                $type,
                '',
                '',
                false,
                300,
                '',
                false,
                false,
                0,
                false,
                false,
                false,
                false,
                false
            );
        }

        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(255, 255, 255);
        $this->SetY($yPos + 5);
        $this->Cell(0, 15, '© '.date('Y').' DepEd | Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
        $this->SetTextColor(0, 0, 0);
    }
}
