<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class IdCardImageService
{
    /**
     * Resolve the templates directory (same logic as IdCardController).
     * Tries: config path, public/id-card-templates, project/TEMPLATE ID/TEMPLATE, sibling ../TEMPLATE ID/TEMPLATE.
     */
    public static function templatesPath(): ?string
    {
        $path = config('id-card.templates_path');
        if ($path !== null && $path !== '') {
            $resolved = str_starts_with($path, '/') || preg_match('#^[A-Za-z]:#', $path)
                ? $path
                : base_path($path);
            if (is_dir($resolved)) {
                return $resolved;
            }
        }
        $candidates = [
            public_path('id-card-templates'),
            base_path('TEMPLATE_ID'),
            base_path('TEMPLATE_ID'.DIRECTORY_SEPARATOR.'TEMPLATE'),
            base_path('../TEMPLATE_ID'),
            base_path('..'.DIRECTORY_SEPARATOR.'TEMPLATE_ID'),
            base_path('..'.DIRECTORY_SEPARATOR.'TEMPLATE_ID'.DIRECTORY_SEPARATOR.'TEMPLATE'),
            base_path('TEMPLATE ID/TEMPLATE'),
            base_path('TEMPLATE ID'),
            base_path('TEMPLATE ID'.DIRECTORY_SEPARATOR.'TEMPLATE'),
            base_path('../TEMPLATE ID/TEMPLATE'),
            base_path('..'.DIRECTORY_SEPARATOR.'TEMPLATE ID'),
            base_path('..'.DIRECTORY_SEPARATOR.'TEMPLATE ID'.DIRECTORY_SEPARATOR.'TEMPLATE'),
        ];
        foreach ($candidates as $dir) {
            if ($dir !== '' && is_dir($dir)) {
                return $dir;
            }
        }
        return null;
    }

    /**
     * Get the full path to the EODB ID BB template PNG.
     * Selection: role (so System Admin etc. get correct template) → job → employment status → fallback.
     *
     * @param  string|null  $employStatus  e.g. "Casual", "Permanent"
     * @param  string|null  $jobShorten  e.g. "TRAINEE" — if a file {jobShorten}.png exists, use it
     * @param  string|null  $role  e.g. "System Admin" — if mapped in role_to_template, that template is used first
     */
    public static function eodbTemplatePath(?string $employStatus = null, ?string $jobShorten = null, ?string $role = null, ?string $jobTitle = null): ?string
    {
        $dir = self::templatesPath();
        if ($dir === null) {
            return null;
        }

        $tryFile = static function (string $filename) use ($dir): ?string {
            $path = $dir . DIRECTORY_SEPARATOR . $filename;
            return File::isFile($path) ? $path : null;
        };

        // 1) Role-based: so "System Admin" / "Admin System" etc. get the right template
        if ($role !== null && $role !== '') {
            $roleMap = config('id-card.role_to_template', []);
            $filename = $roleMap[$role] ?? null;
            if ($filename === null && is_array($roleMap)) {
                foreach ($roleMap as $key => $val) {
                    if (strcasecmp($key, $role) === 0) {
                        $filename = $val;
                        break;
                    }
                }
            }
            if ($filename !== null) {
                $path = $tryFile($filename);
                if ($path !== null) {
                    return $path;
                }
            }

            $roleMatched = self::findTemplateByLabel($dir, $role);
            if ($roleMatched !== null) {
                return $roleMatched;
            }
        }

        // 2) Job-based override: if job_shorten template exists (e.g. TRAINEE.png), use it
        if ($jobShorten !== null && $jobShorten !== '') {
            $path = $tryFile($jobShorten . '.png') ?? $tryFile($jobShorten);
            if ($path !== null) {
                return $path;
            }
        }

        if ($jobTitle !== null && $jobTitle !== '') {
            $jobMatched = self::findTemplateByLabel($dir, $jobTitle);
            if ($jobMatched !== null) {
                return $jobMatched;
            }
        }

        // 3) By employment status: Casual → contractual, Permanent → regular, else official
        $byStatus = config('id-card.eodb_by_status', []);
        if ($employStatus !== null && $employStatus !== '' && is_array($byStatus)) {
            $filename = $byStatus[$employStatus] ?? $byStatus['default'] ?? null;
            if ($filename !== null) {
                $path = $tryFile($filename);
                if ($path !== null) {
                    return $path;
                }
            }
        }
        if (is_array($byStatus) && isset($byStatus['default'])) {
            $path = $tryFile($byStatus['default']);
            if ($path !== null) {
                return $path;
            }
        }

        // 4) Config fallback filename
        $preferred = config('id-card.eodb_id_bb_template', 'EODBBB.png');
        $path = $tryFile($preferred);
        if ($path !== null) {
            return $path;
        }

        // 5) First PNG in directory
        $files = File::files($dir);
        foreach ($files as $file) {
            $path = is_string($file) ? $file : $file->getPathname();
            if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'png') {
                return $path;
            }
        }
        return null;
    }

    private static function findTemplateByLabel(string $dir, string $label): ?string
    {
        $needle = self::normalizeTemplateToken($label);
        if ($needle === '') {
            return null;
        }

        foreach (File::files($dir) as $file) {
            $path = is_string($file) ? $file : $file->getPathname();
            if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'png') {
                continue;
            }

            $base = pathinfo($path, PATHINFO_FILENAME);
            $token = self::normalizeTemplateToken($base);
            if ($token === $needle || str_contains($token, $needle) || str_contains($needle, $token)) {
                return $path;
            }
        }

        return null;
    }

    private static function normalizeTemplateToken(string $value): string
    {
        $upper = strtoupper(trim($value));
        return preg_replace('/[^A-Z0-9]+/', '', $upper) ?? '';
    }

    /**
     * Check whether the GD extension is available (required for image generation).
     */
    public static function gdAvailable(): bool
    {
        return \function_exists('imagecreatefrompng');
    }

    /**
     * Build EODB ID card image (same approach as legacy eodb_idBB.php):
     * 1. Load template PNG (by role → job → employment status).
     * 2. Overlay variable data: name, division, ID no, role label, photo, QR code.
     * The card design (seal, colors, layout) comes from the template; PHP only overlays data.
     *
     * @param  array{fullname: string, employee_id: string, division: string, photo_path: string|null, signature_path?: string|null, employ_status?: string|null, job_shorten?: string|null, job_title?: string|null, role?: string|null}  $data
     */
    public static function buildEodbCard(array $data): ?string
    {
        if (! self::gdAvailable()) {
            throw new \RuntimeException(
                'The PHP GD extension is required to generate ID card images. Enable it in php.ini (e.g. extension=gd) and restart the web server.'
            );
        }

        $templatePath = self::eodbTemplatePath(
            $data['employ_status'] ?? null,
            $data['job_shorten'] ?? null,
            $data['role'] ?? null,
            $data['job_title'] ?? null
        );
        if ($templatePath === null || ! File::isFile($templatePath)) {
            return null;
        }

        $img = @\imagecreatefrompng($templatePath);
        if ($img === false) {
            return null;
        }

        $w = \imagesx($img);
        $h = \imagesy($img);
        if ($w <= 0 || $h <= 0) {
            \imagedestroy($img);
            return null;
        }

        $black = \imagecolorallocate($img, 0, 0, 0);
        if ($black === false) {
            $black = \imagecolorallocate($img, 1, 1, 1);
        }
        $white = \imagecolorallocate($img, 255, 255, 255);

        $fullname = trim($data['fullname'] ?? '');
        $lastName = '';
        $firstMiddle = '';
        if ($fullname !== '' && $fullname !== '—') {
            $parts = preg_split('/\s+/', $fullname, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            if (count($parts) >= 2) {
                $lastName = strtoupper((string) array_pop($parts));
                $firstMiddle = strtoupper(implode(' ', $parts));
            } else {
                $lastName = strtoupper($fullname);
            }
        }
        // Display as "LASTNAME," like the TRAINEE ID
        if ($lastName !== '') {
            $lastName .= ',';
        }

        $employeeId = trim($data['employee_id'] ?? '');
        $division = trim($data['division'] ?? 'DIVISION OFFICE');
        $photoPath = isset($data['photo_path']) && $data['photo_path'] !== '' ? $data['photo_path'] : null;
        $signaturePath = isset($data['signature_path']) && $data['signature_path'] !== '' ? $data['signature_path'] : null;
        $roleLabel = trim($data['role'] ?? '');
        if ($roleLabel !== '') {
            $roleLabel = strtoupper(preg_replace('/\s+/', ' ', $roleLabel));
        }
        // Legacy: Casual = black text, else (Permanent/official/red template) = white text so it's visible
        $employStatus = isset($data['employ_status']) ? (string) $data['employ_status'] : null;
        $textColor = (strcasecmp($employStatus ?? '', 'Casual') === 0) ? $black : $white;

        $font = 5;
        $lineHeight = 18;
        $charW = 8;
        $charH = 12;

        // Two cards side-by-side: template width is often 2× single card
        $twoCards = $w >= (int) round($h * 1.4);
        $cardWidth = $twoCards ? (int) round($w / 2) : $w;

        $drawCard = static function (int $offsetX) use (
            $img, $cardWidth, $h, $textColor, $white, $font, $lineHeight, $charW, $charH,
            $lastName, $firstMiddle, $division, $employeeId, $roleLabel
) {
            $marginLeft = $offsetX + (int) round($cardWidth * 0.08);
            $nameY = (int) round($h * 0.22);
            $divisionY = $nameY + $lineHeight;
            $idY = $divisionY + $lineHeight;

            if ($lastName !== '') {
                \imagestring($img, $font, $marginLeft, $nameY, $lastName, $textColor);
            }
            if ($firstMiddle !== '') {
                \imagestring($img, $font, $marginLeft, $nameY + 12, $firstMiddle, $textColor);
            }
            \imagestring($img, $font, $marginLeft, $divisionY, $division, $textColor);
            if ($employeeId !== '') {
                \imagestring($img, $font, $marginLeft, $idY, 'ID NO.' . $employeeId, $textColor);
            }

            // Role area: cover template text (e.g. "ACCOUNTING III") then draw actual role
            $roleRectX = $offsetX + (int) round($cardWidth * 0.62);
            $roleRectY = (int) round($h * 0.02);
            $roleRectW = (int) round($cardWidth * 0.35);
            $roleRectH = (int) round($h * 0.58);
            $sampleX = min($roleRectX + 5, $offsetX + $cardWidth - 5);
            $sampleY = min($roleRectY + 5, $h - 5);
            if ($sampleX >= 0 && $sampleY >= 0) {
                $sampleIdx = @\imagecolorat($img, $sampleX, $sampleY);
                if ($sampleIdx !== false) {
                    $r = ($sampleIdx >> 16) & 0xFF;
                    $g = ($sampleIdx >> 8) & 0xFF;
                    $b = $sampleIdx & 0xFF;
                    $bgFill = \imagecolorallocate($img, $r, $g, $b);
                    if ($bgFill !== false) {
                        \imagefilledrectangle($img, $roleRectX, $roleRectY, $offsetX + $cardWidth - 2, $roleRectY + $roleRectH, $bgFill);
                    }
                }
            }
            if ($roleLabel !== '') {
                $roleX = $offsetX + (int) round($cardWidth * 0.72);
                $roleY = (int) round($h * 0.12);
                $len = strlen($roleLabel);
                $maxChars = min(20, (int) floor(($h * 0.5) / $charH));
                for ($i = 0; $i < min($len, $maxChars); $i++) {
                    \imagestring($img, $font, $roleX, $roleY + $i * $charH, $roleLabel[$i], $white);
                }
            }
        };

        $drawCard(0);
        if ($twoCards) {
            $drawCard($cardWidth);
        }

        // Photo: draw once centered on first card, and on second card if two-card layout
        if ($photoPath !== null && File::isFile($photoPath)) {
            $photo = self::loadImage($photoPath);
            if ($photo !== null) {
                $photoW = \imagesx($photo);
                $photoH = \imagesy($photo);
                $dstW = (int) round($cardWidth * 0.28);
                $dstH = (int) round($h * 0.45);
                $dstX = (int) round($cardWidth * 0.65);
                $dstY = (int) round($h * 0.48);
                \imagecopyresampled($img, $photo, $dstX, $dstY, 0, 0, $dstW, $dstH, $photoW, $photoH);
                if ($twoCards) {
                    \imagecopyresampled($img, $photo, $cardWidth + $dstX, $dstY, 0, 0, $dstW, $dstH, $photoW, $photoH);
                }
                \imagedestroy($photo);
            }
        }

        // Signature: draw near the lower-right area of each card.
        if ($signaturePath !== null && File::isFile($signaturePath)) {
            $signature = self::loadImage($signaturePath);
            if ($signature !== null) {
                $srcW = \imagesx($signature);
                $srcH = \imagesy($signature);

                if ($srcW > 0 && $srcH > 0) {
                    $dstW = (int) round($cardWidth * 0.24);
                    $dstH = (int) round($h * 0.10);
                    $dstX = (int) round($cardWidth * 0.62);
                    $dstY = (int) round($h * 0.85);

                    \imagealphablending($img, true);
                    \imagesavealpha($img, true);

                    \imagecopyresampled($img, $signature, $dstX, $dstY, 0, 0, $dstW, $dstH, $srcW, $srcH);
                    if ($twoCards) {
                        \imagecopyresampled($img, $signature, $cardWidth + $dstX, $dstY, 0, 0, $dstW, $dstH, $srcW, $srcH);
                    }
                }

                \imagedestroy($signature);
            }
        }

        // QR code (like legacy eodb_idBB.php): employee_id, one per card
        if ($employeeId !== '') {
            $qrSize = (int) round(min($cardWidth, $h) * 0.12);
            $qrX1 = (int) round($cardWidth * 0.05);
            $qrY = (int) round($h * 0.55);
            $qrImg = self::createQrCodeImage($employeeId, $qrSize);
            if ($qrImg !== null) {
                $qrW = \imagesx($qrImg);
                $qrH = \imagesy($qrImg);
                \imagecopyresampled($img, $qrImg, $qrX1, $qrY, 0, 0, $qrSize, $qrSize, $qrW, $qrH);
                if ($twoCards) {
                    \imagecopyresampled($img, $qrImg, $cardWidth + $qrX1, $qrY, 0, 0, $qrSize, $qrSize, $qrW, $qrH);
                }
                \imagedestroy($qrImg);
            }
        }

        ob_start();
        \imagepng($img);
        $png = ob_get_clean();
        \imagedestroy($img);

        return $png ?: null;
    }

    private static function loadImage(string $path)
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'png') {
            return @\imagecreatefrompng($path) ?: null;
        }
        if (in_array($ext, ['jpg', 'jpeg'], true)) {
            return @\imagecreatefromjpeg($path) ?: null;
        }
        if ($ext === 'gif') {
            return @\imagecreatefromgif($path) ?: null;
        }
        return null;
    }

    /**
     * Create a QR code as a GD image resource (for pasting on the card).
     * Uses endroid/qr-code if available; returns null otherwise.
     */
    private static function createQrCodeImage(string $data, int $size): mixed
    {
        if (! class_exists(\Endroid\QrCode\QrCode::class)) {
            return null;
        }
        try {
            $qrCode = new \Endroid\QrCode\QrCode(
                data: $data,
                size: $size,
                margin: 2
            );
            $writer = new \Endroid\QrCode\Writer\PngWriter();
            $result = $writer->write($qrCode);
            $png = $result->getString();
            $res = @\imagecreatefromstring($png);

            return $res ?: null;
        } catch (\Throwable) {
            return null;
        }
    }
}
