<?php

require_once '../assets/tcpdf/tcpdf.php';

// --- Check for POST data ---
if (! isset($_POST['html_data'])) {
    http_response_code(400); // Bad Request
    exit('No HTML data received.');
}

// Get the main HTML table data
$html_table = $_POST['html_data'];

// Get the custom subtitle text, defaulting if not provided
$custom_subtitle = isset($_POST['subtitle_text'])
    ? $_POST['subtitle_text']
    : 'Date Range Not Specified'; // Default value if the subtitle isn't passed

// --- START: Extend the TCPDF class for Custom Header/Footer ---
class MYPDF extends TCPDF
{
    // Property to hold the custom subtitle
    protected $report_subtitle;

    // Constructor to receive the subtitle
    public function __construct($orientation = 'L', $unit = 'in', $format = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false, $report_subtitle_param = '')
    {
        // Store the subtitle passed to the constructor
        $this->report_subtitle = $report_subtitle_param;

        // Call the parent constructor
        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache);
    }

    // Page header
    public function Header()
    {

        $logo_file = '../assets/img/header.png';
        $image_width = 11;

        // 1. Calculate X-coordinate for Centering (A4 Landscape: ~11.7 in wide)
        $page_width = 11.5;
        $left_margin = 1.5;
        $right_margin = 0.6;
        $printable_width = $page_width - $left_margin - $right_margin;
        $x_center = (($printable_width - $image_width) / 2) + $left_margin;

        // --- Center-Aligned Logo (Y: 0.5 in) ---
        $this->Image($logo_file, $x_center, 0.5, $image_width, 2.0, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, true, false, false);

        // --- Header Text ---
        // Y-coordinate is 2.0 to start below the image
        $this->SetFont('helvetica', 'B', 10);
        $header_title = 'WORK FROM HOME INDIVIDUAL ACCOMPLISHMENT REPORT';
        $this->MultiCell(7.0, 0.2, $header_title, 0, 'C', 0, 0.5, 2.4, 2.4, true);

        // --- Custom Subtitle Display ---
        $this->SetFont('helvetica', '', 10);
        // Use the stored property here
        $header_subtitle = $this->report_subtitle;
        $this->MultiCell(6.8, 0.2, $header_subtitle, 0, 'C', 0, 0.5, 2.4, 2.6, true);

        // Draw a separating line (Y: 2.8 in)
        $this->Line(3.4, 2.6, 8.5, 2.6);
    }

    // Page footer (Remains the same as previous)

    public function Footer()
    {
        // --- Custom Background Image for Footer ---

        $footer_image_file = '../assets/img/footer.png'; // **CHANGE THIS PATH**
        $page_width = $this->getPageWidth();
        $footer_height = 1; // Adjust height of your footer area (in PDF_UNIT, e.g., mm)
        $margin_bottom = $this->getBreakMargin(); // Get the current bottom margin (where the footer starts)

        // Calculate the Y position for the image to be placed at the bottom edge of the page
        $y_pos = $this->getPageHeight() - $footer_height - 0.3;

        // Add the image. Coordinates are (x, y, w, h, ...)
        // x=0 and w=$page_width makes it full width
        $this->Image(
            $footer_image_file,
            0,                     // X position (0 for left edge)
            $y_pos,                 // Y position
            $page_width,            // Width (full page width)
            $footer_height,         // Height (adjust to your image aspect ratio/footer height)
            '',                     // Type (auto-detected)
            '',                     // Link
            '',                     // Align
            false,                  // Resize (set to false for a fixed size)
            300,                    // DPI
            '',                     // Alignment of the image (for internal use)
            false,                  // Is mask?
            false,                  // Img mask (image index)
            0,                      // Border
            false,                  // FitBox
            false,                  // Hidden
            false                   // Skip-on-this-page
        );

        // --- Custom Text over the Background ---

        // Set font for the footer text
        $this->SetFont('helvetica', 'I', 8);

        // Set text color (e.g., white if your background image is dark)
        $this->SetTextColor(255, 255, 255);

        // Position the text (slightly above the bottom edge)
        $this->SetY($y_pos + 5);

        // Add a line of text (e.g., copyright and page number)
        $text = '© '.date('Y').' DepEd | Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages();

        // Cell(w, h, txt, border, ln, align)
        $this->Cell(0, 15, $text, 0, false, 'C', 0, '', 0, false, 'T', 'M');

        // Reset text color for main content, although it's usually set in the main script
        $this->SetTextColor(0, 0, 0);
    }
}
// --- END: Extended Class ---

// --- IMPORTANT: Instantiate MYPDF and pass the subtitle ---
// Pass the $custom_subtitle variable into the new constructor
$pdf = new MYPDF('L', 'in', 'A4', true, 'UTF-8', false, $custom_subtitle);
// --- End Change ---

$pdf->SetTitle('Tasklist Report');
$pdf->SetKeywords('DEPED Tasklist Report');

// Set margins. Top margin (3.0 in) to clear the header content.

$pdf->SetMargins(0.6, 3.0, 0.6);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER + 1);
$pdf->SetAutoPageBreak(true, 1.5); // Bottom margin 1.0 in for footer
$pdf->AddPage();

// Output the PDF
$styles = '<style>
    table { border-collapse: collapse; width: 100%; font-family:Calibri; font-size:9pt; }
     td { border: 1px solid #040303ff; padding: 2px; }
     th { font-weight: bold; border: 1px solid #050505ff; background-color: #f2f2f2; text-align: center; }
</style>';

$final_html = $styles.$html_table;

$pdf->writeHTML($final_html, true, false, true, false, '');

// Headers for PDF output
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="tasklist_report.pdf"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

$pdf->Output('tasklist_report.pdf', 'I');
exit;
