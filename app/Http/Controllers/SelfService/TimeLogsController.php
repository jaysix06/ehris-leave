<?php

namespace App\Http\Controllers\SelfService;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;
use setasign\Fpdi\Fpdi;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\Process\Process;

class TimeLogsController extends Controller
{
    public function index(Request $request): Response
    {
        $logsByYear = [];

        if (Schema::hasTable('tbl_attendance')) {
            $hrid = $this->resolveHrid($request->user());
            if ($hrid > 0) {
                $records = Attendance::query()
                    ->where('hrid', $hrid)
                    ->whereNotNull('time_in')
                    ->whereNotNull('time_out')
                    ->orderByDesc('time_in')
                    ->get();

                foreach ($records as $record) {
                    $timeIn = $record->time_in;
                    $timeOut = $record->time_out;
                    $year = (int) $timeIn->format('Y');
                    $week = (int) $timeIn->weekOfYear;
                    $seconds = $timeIn->diffInSeconds($timeOut);
                    $hoursFormatted = $this->formatHours($seconds);

                    if (! isset($logsByYear[$year])) {
                        $logsByYear[$year] = [];
                    }
                    if (! isset($logsByYear[$year][$week])) {
                        $logsByYear[$year][$week] = ['entries' => [], 'totalSeconds' => 0];
                    }

                    $logsByYear[$year][$week]['entries'][] = [
                        'year' => $year,
                        'date_in' => $timeIn->format('F d, Y'),
                        'time_in' => $timeIn->format('h:i A'),
                        'date_out' => $timeOut->format('F d, Y'),
                        'time_out' => $timeOut->format('h:i A'),
                        'hours' => $hoursFormatted,
                    ];
                    $logsByYear[$year][$week]['totalSeconds'] += $seconds;
                }

                foreach (array_keys($logsByYear) as $y) {
                    foreach (array_keys($logsByYear[$y]) as $w) {
                        $logsByYear[$y][$w]['total'] = $this->formatHoursFull($logsByYear[$y][$w]['totalSeconds']);
                        unset($logsByYear[$y][$w]['totalSeconds']);
                    }
                }
            }
        }

        $years = array_keys($logsByYear);
        rsort($years);

        return Inertia::render('SelfService/TimeLogs', [
            'logsByYear' => $logsByYear,
            'years' => $years,
        ]);
    }

    /**
     * Export time logs for a given month as Form 48 (Daily Time Record) PDF.
     */
    public function exportForm48(Request $request): HttpResponse
    {
        $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        $month = (int) $request->input('month');
        $year = (int) $request->input('year');
        $date = Carbon::createFromDate($year, $month, 1);
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        $hrid = $this->resolveHrid($user);
        if ($hrid <= 0 || ! Schema::hasTable('tbl_attendance')) {
            return $this->form48PdfResponse($date, 'N/A', '', '', [], 'No attendance data.');
        }

        $records = Attendance::query()
            ->where('hrid', $hrid)
            ->whereNotNull('time_in')
            ->whereNotNull('time_out')
            ->where('time_in', '>=', $startOfMonth)
            ->where('time_in', '<=', $endOfMonth)
            ->orderBy('time_in')
            ->get();

        $byDay = [];
        foreach ($records as $record) {
            $dayKey = $record->time_in->format('Y-m-d');
            $seconds = $record->time_in->diffInSeconds($record->time_out);
            $dayNoon = $record->time_in->copy()->setTime(12, 0, 0);
            if (! isset($byDay[$dayKey])) {
                $byDay[$dayKey] = [
                    'first_in' => $record->time_in,
                    'last_out' => $record->time_out,
                    'total_seconds' => 0,
                    'am_out' => null,
                    'pm_in' => null,
                ];
            }
            $byDay[$dayKey]['total_seconds'] += $seconds;
            if ($record->time_in < $byDay[$dayKey]['first_in']) {
                $byDay[$dayKey]['first_in'] = $record->time_in;
            }
            if ($record->time_out > $byDay[$dayKey]['last_out']) {
                $byDay[$dayKey]['last_out'] = $record->time_out;
            }
            if ($record->time_in->lt($dayNoon) && ($byDay[$dayKey]['am_out'] === null || $record->time_out > $byDay[$dayKey]['am_out'])) {
                $byDay[$dayKey]['am_out'] = $record->time_out;
            }
            if ($record->time_in->gte($dayNoon) && ($byDay[$dayKey]['pm_in'] === null || $record->time_in < $byDay[$dayKey]['pm_in'])) {
                $byDay[$dayKey]['pm_in'] = $record->time_in;
            }
        }

        $nameStationPosition = $this->getEmployeeNameAndStationForPdf($user);
        $employeeName = $nameStationPosition[0];
        $station = $nameStationPosition[1];
        $position = $nameStationPosition[2];

        $templatePath = $this->getForm48TemplatePath();
        if ($templatePath !== null) {
            try {
                $pdfString = $this->buildForm48PdfFromTemplate($date, $employeeName, $byDay);
                if ($pdfString !== '') {
                    $filename = 'form-48_'.$date->format('Y-m').'.pdf';

                    return response($pdfString, 200, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                    ]);
                }
            } catch (\Throwable $e) {
                // Template PDF may use unsupported compression; fall back to HTML
            }
        }

        $html = $this->buildForm48Html($date, $employeeName, $station, $position, $byDay);
        $fontPaths = $this->ensureDompdfFontDirectoriesExist();
        $pdf = Pdf::setOption([
            'font_dir' => $fontPaths['font_dir'],
            'font_cache' => $fontPaths['font_cache'],
        ])->loadHTML($html)->setPaper([0.0, 0.0, 612.0, 936.0], 'portrait');
        $filename = 'form-48_'.$date->format('Y-m').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * @param  array<string, array{first_in: \Carbon\Carbon, last_out: \Carbon\Carbon, total_seconds: int, am_out: ?\Carbon\Carbon, pm_in: ?\Carbon\Carbon}>  $byDay
     */
    private function form48PdfResponse(Carbon $date, string $employeeName, string $station, string $position, array $byDay, string $emptyMessage = ''): HttpResponse
    {
        $templatePath = $this->getForm48TemplatePath();
        if ($templatePath !== null) {
            try {
                $pdfString = $this->buildForm48PdfFromTemplate($date, $employeeName, $byDay);
                if ($pdfString !== '') {
                    $filename = 'form-48_'.$date->format('Y-m').'.pdf';

                    return response($pdfString, 200, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                    ]);
                }
            } catch (\Throwable $e) {
                // Template PDF may use unsupported compression; fall back to HTML
            }
        }

        $html = $this->buildForm48Html($date, $employeeName, $station, $position, $byDay, $emptyMessage);
        $fontPaths = $this->ensureDompdfFontDirectoriesExist();
        $pdf = Pdf::setOption([
            'font_dir' => $fontPaths['font_dir'],
            'font_cache' => $fontPaths['font_cache'],
        ])->loadHTML($html)->setPaper([0.0, 0.0, 612.0, 936.0], 'portrait');

        return $pdf->download('form-48_'.$date->format('Y-m').'.pdf');
    }

    /**
     * Resolve path to Form 48 template PDF. Place form-48.pdf in project root, storage/app/forms/, or public/forms/.
     * If the template uses PDF compression not supported by FPDI, the export falls back to the HTML-generated form.
     */
    private function getForm48TemplatePath(): ?string
    {
        $paths = [
            base_path('form-48.pdf'),
            storage_path('app/forms/form-48.pdf'),
            public_path('forms/form-48.pdf'),
        ];
        foreach ($paths as $path) {
            if (is_file($path) && is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Try to produce an uncompressed copy of the PDF so FPDI can parse it.
     * Uses qpdf (preferred) or Ghostscript if available. Returns path to temp file or null.
     */
    private function tryDecompressPdfForFpdi(string $sourcePath): ?string
    {
        $tempDir = storage_path('app/temp');
        File::ensureDirectoryExists($tempDir);
        $outputPath = $tempDir.DIRECTORY_SEPARATOR.'form-48-fpdi-'.uniqid('', true).'.pdf';

        $gsOutput = '-sOutputFile='.$outputPath;
        $commands = [
            ['qpdf', '--qdf', $sourcePath, $outputPath],
            ['gs', '-sDEVICE=pdfwrite', '-dCompatibilityLevel=1.4', '-dNOPAUSE', '-dQUIET', '-dBATCH', $gsOutput, $sourcePath],
            ['gswin64c', '-sDEVICE=pdfwrite', '-dCompatibilityLevel=1.4', '-dNOPAUSE', '-dQUIET', '-dBATCH', $gsOutput, $sourcePath],
        ];

        foreach ($commands as $cmd) {
            $process = new Process($cmd);
            $process->setTimeout(30);
            $process->run();
            if ($process->isSuccessful() && is_file($outputPath) && filesize($outputPath) > 0) {
                return $outputPath;
            }
            if (is_file($outputPath)) {
                @unlink($outputPath);
            }
        }

        return null;
    }

    /**
     * Build Form 48 PDF by overlaying data onto the template PDF. Returns raw PDF string.
     *
     * @param  array<string, array{first_in: \Carbon\Carbon, last_out: \Carbon\Carbon, total_seconds: int, am_out: ?\Carbon\Carbon, pm_in: ?\Carbon\Carbon}>  $byDay
     */
    private function buildForm48PdfFromTemplate(Carbon $date, string $employeeName, array $byDay): string
    {
        $templatePath = $this->getForm48TemplatePath();
        if ($templatePath === null) {
            return '';
        }

        $pdf = new Fpdi;
        $decompressedPath = null;
        try {
            try {
                $pdf->setSourceFile($templatePath);
            } catch (\Throwable $e) {
                $decompressedPath = $this->tryDecompressPdfForFpdi($templatePath);
                if ($decompressedPath === null) {
                    throw $e;
                }
                $pdf->setSourceFile($decompressedPath);
            }
        } finally {
            if ($decompressedPath !== null && is_file($decompressedPath)) {
                @unlink($decompressedPath);
            }
        }

        $tplIdx = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($tplIdx);
        $pdf->AddPage($size['orientation'] ?? 'P', [$size['width'], $size['height']]);
        $pdf->useTemplate($tplIdx);

        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetTextColor(0, 0, 0);

        $monthName = $date->format('F');
        $year = $date->format('Y');
        $daysInMonth = $date->daysInMonth;

        $w = $size['width'] ?? 210;
        $h = $size['height'] ?? 297;
        $isLetter = $w < 220;
        $nameX = $isLetter ? 18 : 22;
        $nameY = $isLetter ? 38 : 42;
        $monthX = $isLetter ? 95 : 105;
        $monthY = $isLetter ? 44 : 48;
        $yearX = $isLetter ? 125 : 138;
        $tableY0 = $isLetter ? 62 : 68;
        $rowStep = $isLetter ? 4.2 : 4.8;
        $colAmArr = $isLetter ? 32 : 38;
        $colAmDep = $isLetter ? 44 : 50;
        $colPmArr = $isLetter ? 56 : 62;
        $colPmDep = $isLetter ? 68 : 74;
        $colUtH = $isLetter ? 80 : 86;
        $colUtM = $isLetter ? 88 : 96;

        $pdf->SetXY($nameX, $nameY);
        $pdf->Cell(80, 5, $employeeName, 0, 0, 'L');
        $pdf->SetXY($monthX, $monthY);
        $pdf->Cell(25, 5, $monthName, 0, 0, 'L');
        $pdf->SetXY($yearX, $monthY);
        $pdf->Cell(15, 5, $year, 0, 0, 'L');

        $amArr = '';
        $amDep = '';
        $pmArr = '';
        $pmDep = '';
        $utHours = '';
        $utMins = '';
        for ($day = 1; $day <= 31; $day++) {
            $y = $tableY0 + ($day - 1) * $rowStep;
            $dayKey = $day <= $daysInMonth ? $date->copy()->day($day)->format('Y-m-d') : '';
            $data = ($dayKey !== '' && isset($byDay[$dayKey])) ? $byDay[$dayKey] : null;
            if ($data) {
                $firstIn = $data['first_in'];
                $lastOut = $data['last_out'];
                $noon = $firstIn->copy()->setTime(12, 0, 0);
                $cap1245 = $firstIn->copy()->setTime(12, 45, 0);
                $floor1215 = $firstIn->copy()->setTime(12, 15, 0);

                $amArr = '';
                $amDep = '';
                $pmArr = '';
                $pmDep = '';

                $amOut = $data['am_out'] ?? null;
                $pmIn = $data['pm_in'] ?? null;

                $workedSeconds = 0;

                if ($amOut !== null && $firstIn->lt($noon)) {
                    $amArrTime = $firstIn;
                    $amDepTime = $amOut->gt($cap1245) ? $cap1245 : $amOut;
                    $amArr = $amArrTime->format('g:i');
                    $amDep = $amDepTime->format('g:i');
                    $workedSeconds += $amArrTime->diffInSeconds($amDepTime);
                }

                if ($pmIn !== null || $firstIn->gte($noon)) {
                    $basePmIn = $pmIn ?? $firstIn;
                    $pmArrTime = $basePmIn->lt($floor1215) ? $floor1215 : $basePmIn;
                    $pmArr = $pmArrTime->format('g:i');
                    $pmDep = $lastOut->format('g:i');
                    $workedSeconds += $pmArrTime->diffInSeconds($lastOut);
                }

                $requiredSeconds = 8 * 3600;
                if ($workedSeconds < $requiredSeconds) {
                    $undertimeSeconds = $requiredSeconds - $workedSeconds;
                    $utHours = (string) (int) floor($undertimeSeconds / 3600);
                    $utMins = (string) (int) floor(($undertimeSeconds % 3600) / 60);
                } else {
                    $utHours = '';
                    $utMins = '';
                }
            } else {
                $amArr = '';
                $amDep = '';
                $pmArr = '';
                $pmDep = '';
                $utHours = '';
                $utMins = '';
            }
            $pdf->SetFont('Helvetica', '', 7);
            $pdf->SetXY($colAmArr, $y);
            $pdf->Cell(10, 4, $amArr, 0, 0, 'C');
            $pdf->SetXY($colAmDep, $y);
            $pdf->Cell(10, 4, $amDep, 0, 0, 'C');
            $pdf->SetXY($colPmArr, $y);
            $pdf->Cell(10, 4, $pmArr, 0, 0, 'C');
            $pdf->SetXY($colPmDep, $y);
            $pdf->Cell(10, 4, $pmDep, 0, 0, 'C');
            $pdf->SetXY($colUtH, $y);
            $pdf->Cell(6, 4, $utHours, 0, 0, 'C');
            $pdf->SetXY($colUtM, $y);
            $pdf->Cell(6, 4, $utMins, 0, 0, 'C');
        }

        return $pdf->Output('S');
    }

    /**
     * @param  array<string, array{first_in: \Carbon\Carbon, last_out: \Carbon\Carbon, total_seconds: int, am_out: ?\Carbon\Carbon, pm_in: ?\Carbon\Carbon}>  $byDay
     */
    private function buildForm48Html(Carbon $date, string $employeeName, string $station, string $position, array $byDay, string $emptyMessage = ''): string
    {
        $h = fn (string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $monthName = $date->format('F');
        $year2 = $date->format('Y');
        $daysInMonth = $date->daysInMonth;

        $rows = '';
        for ($day = 1; $day <= 31; $day++) {
            $dayKey = $day <= $daysInMonth ? $date->copy()->day($day)->format('Y-m-d') : '';
            $data = ($dayKey !== '' && isset($byDay[$dayKey])) ? $byDay[$dayKey] : null;
            if ($data) {
                $firstIn = $data['first_in'];
                $lastOut = $data['last_out'];
                $noon = $firstIn->copy()->setTime(12, 0, 0);
                $cap1245 = $firstIn->copy()->setTime(12, 45, 0);
                $floor1215 = $firstIn->copy()->setTime(12, 15, 0);

                $amArr = '';
                $amDep = '';
                $pmArr = '';
                $pmDep = '';

                $amOut = $data['am_out'] ?? null;
                $pmIn = $data['pm_in'] ?? null;

                $hasAm = $amOut !== null && $firstIn->lt($noon);
                $hasPm = $pmIn !== null || $firstIn->gte($noon);

                $workedSeconds = 0;

                if ($hasAm) {
                    $amArrTime = $firstIn;
                    $amDepTime = $amOut->gt($cap1245) ? $cap1245 : $amOut;
                    $amArr = $amArrTime->format('g:i');
                    $amDep = $amDepTime->format('g:i');
                    $workedSeconds += $amArrTime->diffInSeconds($amDepTime);
                }

                if ($hasPm) {
                    $basePmIn = $pmIn ?? $firstIn;
                    $pmArrTime = $basePmIn->lt($floor1215) ? $floor1215 : $basePmIn;
                    $pmArr = $pmArrTime->format('g:i');
                    $pmDep = $lastOut->format('g:i');
                    $workedSeconds += $pmArrTime->diffInSeconds($lastOut);
                }

                $requiredSeconds = 8 * 3600;
                if ($workedSeconds < $requiredSeconds) {
                    $undertimeSeconds = $requiredSeconds - $workedSeconds;
                    $utHours = (string) (int) floor($undertimeSeconds / 3600);
                    $utMins = (string) (int) floor(($undertimeSeconds % 3600) / 60);
                } else {
                    $utHours = '';
                    $utMins = '';
                }
            } else {
                $amArr = $day <= $daysInMonth ? '&nbsp;' : '&nbsp;';
                $amDep = '&nbsp;';
                $pmArr = '&nbsp;';
                $pmDep = '&nbsp;';
                $utHours = '&nbsp;';
                $utMins = '&nbsp;';
            }
            $rows .= '<tr>'
                .'<td class="f48-day">'.$day.'</td>'
                .'<td class="f48-cell">'.$amArr.'</td><td class="f48-cell">'.$amDep.'</td>'
                .'<td class="f48-cell">'.$pmArr.'</td><td class="f48-cell">'.$pmDep.'</td>'
                .'<td class="f48-cell-ut">'.$utHours.'</td><td class="f48-cell-ut">'.$utMins.'</td>'
                .'</tr>';
        }

        $emptyBlock = $emptyMessage !== ''
            ? '<p style="text-align:center;padding:6px;color:#555;font-size:10px;">'.$h($emptyMessage).'</p>'
            : '';

        $singleForm = '<div class="f48-top-left">Civil Service Form No. 48</div>'
            .'<div class="f48-title">DAILY TIME RECORD</div>'
            .'<div class="f48-info f48-name-block"><span class="f48-name-line">'.$h($employeeName).'</span></div>'
            .'<div class="f48-info f48-label-name f48-label-below">(Name)</div>'
            .'<div class="f48-info">For the month of <span class="f48-info-line f48-info-line-center" style="min-width:90px;">'.$h($monthName).'</span>, <span class="f48-info-line f48-info-line-center" style="min-width:36px;">'.$h($year2).'</span></div>'
            .'<div class="f48-info f48-hours-row"><span class="f48-hours-left">Official hours of arrival</span> Regular Days <span class="f48-info-line f48-days-line">8:00 AM - 5:00 PM</span></div>'
            .'<div class="f48-info f48-hours-row"><span class="f48-hours-left">and departure</span> Saturdays <span class="f48-info-line f48-days-line">&nbsp;</span></div>'
            .$emptyBlock
            .'<div class="f48-header-sep"></div>'
            .'<table class="f48-table">'
            .'<thead><tr>'
            .'<th rowspan="2" class="f48-day">Days</th>'
            .'<th colspan="2" class="f48-cell">A. M.</th>'
            .'<th colspan="2" class="f48-cell">P. M.</th>'
            .'<th colspan="2" class="f48-cell-ut">UNDER TIME</th>'
            .'</tr><tr class="f48-subheader-row">'
            .'<th class="f48-cell f48-th-sub">ARRIVAL</th><th class="f48-cell f48-th-sub">DEPARTURE</th>'
            .'<th class="f48-cell f48-th-sub">ARRIVAL</th><th class="f48-cell f48-th-sub">DEPARTURE</th>'
            .'<th class="f48-cell-ut f48-th-sub">Hours</th><th class="f48-cell-ut f48-th-sub">Minutes</th>'
            .'</tr></thead><tbody>'
            .$rows
            .'<tr class="f48-total-row"><td class="f48-day">TOTAL</td><td colspan="2" class="f48-cell">&nbsp;</td><td colspan="2" class="f48-cell">&nbsp;</td><td class="f48-cell-ut">&nbsp;</td><td class="f48-cell-ut">&nbsp;</td></tr>'
            .'</tbody></table>'
            .'<div class="f48-cert-block">'
            .'<p class="f48-cert">I CERTIFY on my honor that the above is a true and correct report of the hours of work performed, record of which was made daily at the time of arrival and departure from office.</p>'
            .'</div>'
            .'<div class="f48-incharge-block">'
            .'<div class="f48-sig-line"></div>'
            .'<div class="f48-incharge">In-Charge</div>'
            .'</div>'
            .'<div class="f48-note">(See Instructions on back)</div>';

        $instructionsHtml = $this->buildForm48InstructionsHtml();

        $css = '
            @page { size: 612pt 936pt; margin: 10mm; }
            body { font-family: DejaVu Sans, sans-serif; font-size: 11px; margin: 0; padding: 0; line-height: 1.3; }
            .f48-form-page { width: 94%; margin: 0 18pt 0 10pt; }
            .f48-form-column { width: 50%; float: left; box-sizing: border-box; padding-right: 10px; }
            .f48-instructions-side { width: 50%; float: left; box-sizing: border-box; padding-left: 10px; padding-right: 10px; min-height: 1px; font-size: 10px; font-weight: bold; line-height: 1.4; text-align: justify; }
            .f48-instructions { padding-top: 22px; }
            .f48-top-left { margin-bottom: 6px; font-size: 11px; }
            .f48-title { text-align: center; font-weight: bold; font-size: 13px; margin: 2px 0 10px 0; letter-spacing: 0.5px; border-bottom: 1.5px solid #000; padding-bottom: 2px; }
            .f48-label-name { font-size: 11px; margin-bottom: 1px; }
            .f48-label-below { text-align: center; margin-top: 0; margin-bottom: 5px; }
            .f48-name-block { text-align: center; }
            .f48-name-line { border-bottom: 1.5px dashed #000; min-width: 180px; display: inline-block; padding: 0 2px 1px 0; font-weight: bold; }
            .f48-info { margin-bottom: 5px; font-size: 11px; }
            .f48-info-line { border-bottom: 1.5px solid #000; display: inline-block; margin-left: 2px; padding: 0 4px 0 0; min-height: 10px; vertical-align: bottom; }
            .f48-info-line-center { text-align: center; }
            .f48-info-line-long { min-width: 120px; }
            .f48-hours-left { display: inline-block; width: 160px; vertical-align: top; }
            .f48-days-line { width: 75px; min-width: 75px; margin-left: 4px; }
            .f48-hours-row { margin-bottom: 3px; }
            .f48-header-sep { border-top: 2px solid #000; margin-top: 8px; margin-bottom: 0; }
            .f48-table { width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: fixed; }
            .f48-table th, .f48-table td { border: 1px solid #000; padding: 3px 2px; vertical-align: middle; font-size: 10px; }
            .f48-table th { font-weight: bold; text-align: center; }
            .f48-table .f48-th-sub { font-size: 7px; padding: 2px 1px; line-height: 1.2; overflow: hidden; }
            .f48-table .f48-day { width: 8%; text-align: center; }
            .f48-table .f48-cell { width: 15%; text-align: center; min-width: 0; }
            .f48-table .f48-cell-ut { width: 8%; text-align: center; min-width: 0; }
            .f48-total-row { font-weight: bold; }
            .f48-total-row td { border: 1px solid #000; border-top: 2px double #000; padding: 4px 3px; line-height: 1.2; font-size: 7px; }
            .f48-total-row td:first-child { padding-left: 4px; }
            .f48-cert-block { border-top: 2px double #000; border-bottom: 2px double #000; margin-top: 10px; padding: 10px 0; }
            .f48-cert { margin: 0; font-size: 10px; text-align: left; line-height: 1.35; font-style: italic; }
            .f48-incharge-block { margin-top: 14px; text-align: right; }
            .f48-sig-line { border-bottom: 2px solid #000; width: 240px; margin: 0 0 4px 0; margin-left: auto; display: block; min-height: 12px; }
            .f48-incharge { font-size: 11px; font-weight: bold; font-style: italic; margin: 0; }
            .f48-note { text-align: center; font-size: 9px; font-weight: normal; margin-top: 6px; }
            .f48-instructions-title { font-weight: bold; text-align: center; border-bottom: 1.5px solid #000; padding-bottom: 2px; font-size: 13px; margin: 0 0 10px 0; letter-spacing: 0.5px; }
            .f48-instructions p { margin: 0 0 8px 0; text-align: justify; text-indent: 1.5em; }
            .f48-instructions .f48-note-block { font-size: 10px; font-weight: bold; margin-top: 12px; padding-top: 10px; border-top: 1px solid #000; padding-left: 0; text-indent: 1.5em; }
        ';

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>'.$css.'</style></head><body>'
            .'<div class="f48-form-page">'
            .'<div class="f48-form-column">'.$singleForm.'</div>'
            .'<div class="f48-instructions-side"><div class="f48-instructions">'.$instructionsHtml.'</div></div>'
            .'</div>'
            .'</body></html>';

        return $html;
    }

    /**
     * Instructions text for Civil Service Form No. 48 (back of form). Returned as HTML fragment.
     */
    private function buildForm48InstructionsHtml(): string
    {
        return '<div class="f48-instructions-title">INSTRUCTIONS</div>'
            .'<p>Civil Service Form No. 48, after completion, Should be filed in the records of the Bureau or Office which submits the monthly report on Civil Service Form No. 3 to the Bureau of Civil Service.</p>'
            .'<p>Court interpreters and stenographers who accompany judges shall fill out the daily time reports on this form in triplicate, which must be approved by the judge or an officer of the Department of Justice. The original should be forwarded promptly after the end of the month to the Bureau of Civil Service, thru the Department of Justice, the duplicate to be kept in the Department of Justice; and the triplicate, in the office of the Clerk of Court where service was rendered.</p>'
            .'<p>In the space provided for the purpose on the other side will be indicated the office hours the employee is required to observe, as for example, "Regular days, 8:00 to 12:00 and 1:00 to 4:00; Saturdays 8:00 to 1:00."</p>'
            .'<p>Paragraph 3, Civil Service Rule XV, Executive Order No. 5, series of 1909, provides: "Each chief of a Bureau or Office shall require a daily record of attendance of all the officers and employees under him entitled to leave or absence or vacation (including teachers) to be kept on the proper form and also a systematic office record showing for each day all absences from duty from any cause whatever. At the beginning of each month he shall report to the Commissioner on the proper form of all absences from any cause whatever, including the exact amount of undertime of each person for each day. Officers or employees serving in the field or on the water need not be required to keep a daily record, but all absences of such employees must be included in the monthly report of changes and absences. Falsification of time records will render the offending officers or employee liable to summary removal from the service and criminal prosecution."</p>'
            .'<p class="f48-note-block">(NOTE A record made from memory at sometime subsequent to the occurrence of an event is not reliable. Non observance of office hours deprives the employee of the leave privileges although he may have rendered overtime service. Where service rendered outside of the Office for the whole morning or afternoon, notation to that effect should be made clearly.)</p>';
    }

    /** @return array{0: string, 1: string, 2: string} [displayName, station, position] */
    private function getEmployeeNameAndStationForPdf(mixed $user): array
    {
        if (! $user) {
            return ['', '', ''];
        }
        $hrid = $this->resolveHrid($user);
        if ($hrid <= 0) {
            return [$user->name ?? $user->email ?? 'N/A', '', ''];
        }
        $emp = Employee::query()->where('hrid', $hrid)->first();
        if (! $emp) {
            return [$user->name ?? $user->email ?? 'N/A', '', ''];
        }
        $first = trim((string) ($emp->firstname ?? ''));
        $middle = trim((string) ($emp->middlename ?? ''));
        $last = trim((string) ($emp->lastname ?? ''));
        $middleInitial = $middle !== '' ? mb_substr($middle, 0, 1).'.' : '';
        $name = trim(implode(' ', array_filter([$first, $middleInitial, $last])));
        if ($name === '') {
            $name = $user->name ?? $user->email ?? 'N/A';
        }
        $station = $this->resolveStationForEmployee($emp);
        $position = trim((string) ($emp->job_title ?? ''));

        return [$name, $station, $position];
    }

    private function resolveStationForEmployee(Employee $employee): string
    {
        $office = trim((string) ($employee->office ?? ''));
        if ($office === '') {
            return '';
        }
        if (! Schema::hasTable('tbl_department') || ! Schema::hasColumn('tbl_department', 'department_name')) {
            return $office;
        }
        $dept = Department::query()
            ->select('department_name')
            ->where('department_id', $office)
            ->first();
        if ($dept && trim((string) $dept->department_name) !== '') {
            return trim((string) $dept->department_name);
        }

        return $office;
    }

    /**
     * @return array{font_dir: string, font_cache: string}
     */
    private function ensureDompdfFontDirectoriesExist(): array
    {
        $fontDir = (string) config('dompdf.options.font_dir', storage_path('fonts'));
        $fontCache = (string) config('dompdf.options.font_cache', storage_path('fonts'));
        if (! File::isDirectory($fontDir)) {
            File::ensureDirectoryExists($fontDir, 0755, true);
        }
        if (! File::isDirectory($fontCache)) {
            File::ensureDirectoryExists($fontCache, 0755, true);
        }

        return ['font_dir' => $fontDir, 'font_cache' => $fontCache];
    }

    private function formatHours(int $totalSeconds): string
    {
        $hours = (int) floor($totalSeconds / 3600);
        $minutes = (int) floor(($totalSeconds % 3600) / 60);

        return sprintf('%d:%02d', $hours, $minutes);
    }

    private function formatHoursFull(int $totalSeconds): string
    {
        $hours = (int) floor($totalSeconds / 3600);
        $minutes = (int) floor(($totalSeconds % 3600) / 60);
        $seconds = (int) ($totalSeconds % 60);

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    private function resolveHrid(mixed $authUser): int
    {
        if ($authUser === null) {
            return 0;
        }

        if (! empty($authUser->hrId) && (int) $authUser->hrId > 0) {
            return (int) $authUser->hrId;
        }

        if (Schema::hasTable('tbl_user')) {
            $profile = User::query()
                ->select('hrId', 'userId')
                ->where('email', $authUser->email ?? '')
                ->first();

            if ($profile && ! empty($profile->hrId) && (int) $profile->hrId > 0) {
                return (int) $profile->hrId;
            }
        }

        if (Schema::hasTable('tbl_emp_official_info') && Schema::hasColumn('tbl_emp_official_info', 'email')) {
            $email = trim((string) ($authUser->email ?? ''));
            if ($email !== '') {
                $employee = Employee::query()
                    ->select('hrid')
                    ->where('email', $email)
                    ->first();
                if ($employee && ! empty($employee->hrid)) {
                    return (int) $employee->hrid;
                }
            }
        }

        $userId = $authUser->userId ?? $authUser->id ?? $authUser->getKey();
        if ($userId !== null && (int) $userId > 0) {
            return (int) $userId;
        }

        return 0;
    }
}
