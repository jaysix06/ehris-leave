<?php

namespace App\Http\Controllers\SelfService;

use App\Http\Controllers\Controller;
use App\Models\EmpOfficialInfo;
use App\Models\EmpPersonalInfo;
use App\Models\RequestedId;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IdCardController extends Controller
{
    /**
     * Resolve the directory path for ID card templates (PNG files).
     * Tries env ID_CARD_TEMPLATES_PATH, then public/id-card-templates, then ../TEMPLATE ID/TEMPLATE.
     */
    protected function templatesPath(): ?string
    {
        $path = config('id-card.templates_path');
        if ($path !== null && $path !== '') {
            $resolved = str_starts_with($path, '/') || preg_match('#^[A-Za-z]:#', $path)
                ? $path
                : base_path($path);

            return is_dir($resolved) ? $resolved : null;
        }
        $public = public_path('id-card-templates');
        if (is_dir($public)) {
            return $public;
        }
        $sibling = base_path('../TEMPLATE ID/TEMPLATE');

        return is_dir($sibling) ? $sibling : null;
    }

    /**
     * List PNG filenames (basenames) from the templates directory.
     *
     * @return array<int, string>
     */
    protected function listTemplates(): array
    {
        $path = $this->templatesPath();
        if ($path === null) {
            return [];
        }
        $files = @scandir($path);
        if ($files === false) {
            return [];
        }
        $list = [];
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            if (str_ends_with(strtolower($file), '.png')) {
                $list[] = $file;
            }
        }
        sort($list);

        return $list;
    }

    /**
     * Load profile and ID-relevant data for the authenticated user (by hrid).
     */
    public function show(Request $request)
    {
        $authUser = $request->user();
        $profile = null;
        $officialInfo = null;
        $personalInfo = null;
        $contactInfo = null;
        $hrid = null;
        $email = $authUser?->email;

        if ($authUser && Schema::hasTable('tbl_user')) {
            $profile = User::query()
                ->select([
                    'userId',
                    'hrId',
                    'email',
                    'lastname',
                    'firstname',
                    'middlename',
                    'extname',
                    'avatar',
                    'job_title',
                    'role',
                    'fullname',
                ])
                ->where('email', $authUser->email)
                ->first();
            $email = $profile?->email ?? $email;
            $hrid = $profile?->hrId
                ?? $authUser->hrId
                ?? $authUser->userId
                ?? $authUser->id
                ?? null;
        }

        // Load details (prefer HRID; if missing or no row, fallback to email when possible)
        if (Schema::hasTable('tbl_emp_official_info')) {
            if ($hrid !== null) {
                $officialInfo = EmpOfficialInfo::query()->where('hrid', $hrid)->first();
            }
            if ($officialInfo === null && $email && Schema::hasColumn('tbl_emp_official_info', 'email')) {
                $officialInfo = EmpOfficialInfo::query()->where('email', $email)->first();
            }
        }

        if (Schema::hasTable('tbl_emp_personal_info')) {
            if ($hrid !== null) {
                $personalInfo = EmpPersonalInfo::query()->where('hrid', $hrid)->first();
            }
            if ($personalInfo === null && $email && Schema::hasColumn('tbl_emp_personal_info', 'email')) {
                $personalInfo = EmpPersonalInfo::query()->where('email', $email)->first();
            }
        }

        if (Schema::hasTable('tbl_emp_contact_info')) {
            $contactQuery = DB::table('tbl_emp_contact_info as c')
                ->leftJoin('tbl_barangay as rb', 'rb.barangay_id', '=', 'c.barangay')
                ->leftJoin('tbl_province as rp', 'rp.province_id', '=', 'c.province')
                ->leftJoin('tbl_barangay as pb', 'pb.barangay_id', '=', 'c.barangay1')
                ->leftJoin('tbl_province as pp', 'pp.province_id', '=', 'c.province1')
                ->select([
                    'c.*',
                    'rb.barangay_name as residential_barangay_name',
                    'rp.province_name as residential_province_name',
                    'pb.barangay_name as permanent_barangay_name',
                    'pp.province_name as permanent_province_name',
                ]);

            if ($hrid !== null) {
                $contactInfo = (clone $contactQuery)->where('c.hrid', $hrid)->first();
            }

            if ($contactInfo === null && $email && Schema::hasColumn('tbl_emp_contact_info', 'email')) {
                $contactInfo = (clone $contactQuery)->where('c.email', $email)->first();
            }
        }

        $templates = $this->listTemplates();
        $templateBaseUrl = url('/self-service/id-card/template');
        $signaturePath = $this->resolveStoredSignaturePath($hrid, $email);

        return Inertia::render('SelfService/IdCard', [
            'profile' => $profile,
            'officialInfo' => $officialInfo,
            'personalInfo' => $personalInfo,
            'contactInfo' => $contactInfo,
            'templates' => $templates,
            'templateBaseUrl' => $templateBaseUrl,
            'signaturePath' => $signaturePath,
        ]);
    }

    /**
     * Stream an ID card template image by filename (safe basename only).
     */
    public function template(Request $request, string $filename): StreamedResponse
    {
        $path = $this->templatesPath();
        if ($path === null) {
            abort(404, 'ID card templates not configured.');
        }
        $basename = basename($filename);
        if ($basename === '' || $basename !== $filename || str_contains($basename, '..')) {
            abort(404, 'Invalid template name.');
        }
        $fullPath = $path.DIRECTORY_SEPARATOR.$basename;
        if (! is_file($fullPath) || ! str_ends_with(strtolower($basename), '.png')) {
            abort(404, 'Template not found.');
        }

        return response()->streamDownload(function () use ($fullPath) {
            $stream = fopen($fullPath, 'rb');
            if ($stream) {
                fpassthru($stream);
                fclose($stream);
            }
        }, $basename, [
            'Content-Type' => 'image/png',
        ]);
    }

    /**
     * Update ID-relevant user details (official, personal, contact).
     */
    public function update(Request $request): RedirectResponse
    {
        $authUser = $request->user();
        $profile = $authUser && Schema::hasTable('tbl_user')
            ? User::query()->where('email', $authUser->email)->first()
            : null;
        $currentKeyHrid = $profile?->hrId ?? $profile?->userId ?? $authUser?->userId ?? $authUser?->id ?? null;

        if ($currentKeyHrid === null) {
            return redirect()->route('self-service.id-card')
                ->withErrors(['message' => 'Unable to identify employee.']);
        }

        $data = $request->validate([
            'hrid' => ['nullable', 'string', 'max:64'],
            'employee_id' => ['nullable', 'string', 'max:64'],
            'prefix_name' => ['nullable', 'string', 'max:32'],
            'firstname' => ['nullable', 'string', 'max:128'],
            'middlename' => ['nullable', 'string', 'max:128'],
            'lastname' => ['nullable', 'string', 'max:128'],
            'extension' => ['nullable', 'string', 'max:32'],
            'birth_date' => ['nullable', 'string', 'max:32'],
            'prc_no' => ['nullable', 'string', 'max:64'],
            'tin' => ['nullable', 'string', 'max:64'],
            'gsis' => ['nullable', 'string', 'max:64'],
            'gsis_bp' => ['nullable', 'string', 'max:64'],
            'pag_ibig' => ['nullable', 'string', 'max:64'],
            'philhealth' => ['nullable', 'string', 'max:64'],
            'blood_type' => ['nullable', 'string', 'max:16'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'emergency_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact' => ['nullable', 'string', 'max:64'],
            'emergency_email' => ['nullable', 'string', 'max:255'],
            'id_photo' => ['nullable', 'file', 'image', 'max:10240'],
            'signature' => ['nullable', 'file', 'image', 'max:10240'],
        ]);

        // If the user entered an HRID, persist it to tbl_user.hrId and migrate existing rows if needed.
        $requestedHridRaw = trim((string) ($data['hrid'] ?? ''));
        $requestedHrid = ctype_digit($requestedHridRaw) ? (int) $requestedHridRaw : null;

        if ($requestedHrid !== null && $requestedHrid > 0 && $profile instanceof User) {
            $oldHrid = $currentKeyHrid;
            if ((int) ($profile->hrId ?? 0) !== $requestedHrid) {
                $profile->hrId = $requestedHrid;
                $profile->save();
            }

            // If we previously keyed employee records by userId (or an old hrid), migrate them to the new HRID.
            if ($oldHrid !== $requestedHrid) {
                foreach (['tbl_emp_official_info', 'tbl_emp_personal_info', 'tbl_emp_contact_info'] as $table) {
                    if (Schema::hasTable($table) && Schema::hasColumn($table, 'hrid')) {
                        DB::table($table)->where('hrid', $oldHrid)->update(['hrid' => $requestedHrid]);
                    }
                }
            }
        }

        // Recompute key hrid after any update/migration.
        if ($profile instanceof User) {
            $profile->refresh();
        }
        $hrid = $profile?->hrId ?? $profile?->userId ?? $currentKeyHrid;

        $officialColumns = [
            'employee_id', 'prefix_name', 'firstname', 'middlename', 'lastname', 'extension',
            'job_title',
        ];
        $personalColumns = [
            'dob' => 'birth_date',
            'prc_no' => 'prc_no',
            'tin' => 'tin',
            'gsis' => 'gsis',
            'gsis_bp' => 'gsis_bp',
            'pag_ibig' => 'pag_ibig',
            'philhealth' => 'philhealth',
            'blood_type' => 'blood_type',
        ];
        $contactEmergencyColumns = [
            // Actual columns in tbl_emp_contact_info are emergency_name, emergency_num, emergency_email
            'emergency_name' => 'emergency_name',
            'emergency_contact' => 'emergency_num',
            'emergency_email' => 'emergency_email',
        ];

        if (Schema::hasTable('tbl_emp_official_info')) {
            $officialPayload = [];
            foreach ($officialColumns as $key) {
                if (! array_key_exists($key, $data)) {
                    continue;
                }
                if (Schema::hasColumn('tbl_emp_official_info', $key)) {
                    $officialPayload[$key] = $data[$key] ?: null;
                }
            }
            if ($officialPayload !== []) {
                // Upsert so first-time users get a row created.
                $base = ['hrid' => $hrid];
                if ($profile?->email && Schema::hasColumn('tbl_emp_official_info', 'email')) {
                    $base['email'] = $profile->email;
                }
                DB::table('tbl_emp_official_info')->updateOrInsert(
                    ['hrid' => $hrid],
                    array_merge($base, $officialPayload),
                );
            }
        }

        if (Schema::hasTable('tbl_emp_personal_info')) {
            $personalPayload = [];
            foreach ($personalColumns as $dbCol => $requestKey) {
                if (! array_key_exists($requestKey, $data) || ! Schema::hasColumn('tbl_emp_personal_info', $dbCol)) {
                    continue;
                }
                $personalPayload[$dbCol] = $data[$requestKey] ?: null;
            }
            if ($personalPayload !== []) {
                DB::table('tbl_emp_personal_info')->updateOrInsert(
                    ['hrid' => $hrid],
                    array_merge(['hrid' => $hrid], $personalPayload),
                );
            }
        }

        if (Schema::hasTable('tbl_emp_contact_info')) {
            $contactPayload = [];
            foreach ($contactEmergencyColumns as $requestKey => $dbCol) {
                if (! array_key_exists($requestKey, $data)) {
                    continue;
                }
                if (Schema::hasColumn('tbl_emp_contact_info', $dbCol)) {
                    $contactPayload[$dbCol] = $data[$requestKey] ?: null;
                }
            }
            if ($contactPayload !== []) {
                $base = ['hrid' => $hrid];
                if ($profile?->email && Schema::hasColumn('tbl_emp_contact_info', 'email')) {
                    $base['email'] = $profile->email;
                }
                DB::table('tbl_emp_contact_info')->updateOrInsert(
                    ['hrid' => $hrid],
                    array_merge($base, $contactPayload),
                );
            }
        }

        // Create or update ID card request so it appears in Employee Management → ID Card Printing.
        if (Schema::hasTable('tbl_requested_id')) {
            $userId = $profile?->userId ?? $authUser?->userId ?? $authUser?->id ?? null;
            $email = $profile?->email ?? $authUser?->email;
            $fullname = trim(implode(' ', array_filter([
                $data['firstname'] ?? '',
                $data['middlename'] ?? '',
                $data['lastname'] ?? '',
                $data['extension'] ?? '',
            ])));
            if ($fullname === '' && $profile) {
                $fullname = trim((string) ($profile->fullname ?? $profile->name ?? ''));
            }
            $payload = [
                'hrid' => $hrid,
                'fullname' => $fullname ?: null,
                'email' => $email,
                'status' => 'On Process',
                'updated_at' => now(),
            ];
            if ($userId !== null) {
                RequestedId::query()->updateOrInsert(['user_id' => $userId], array_merge($payload, ['user_id' => $userId]));
            } elseif ($email !== null && $email !== '') {
                $existing = RequestedId::query()->where('email', $email)->first();
                if ($existing) {
                    $existing->update($payload);
                } else {
                    RequestedId::query()->create(array_merge($payload, ['email' => $email]));
                }
            }
        }

        $fileKey = (string) ($profile?->userId ?? $hrid);
        $uploadedPhotoPath = null;
        $uploadedSignPath = null;

        try {
            if ($request->hasFile('id_photo')) {
                $uploadedPhotoPath = $this->storePublicUpload(
                    $request->file('id_photo'),
                    'uploads/'.$hrid,
                    (string) $hrid,
                    8 / 10
                );

                if ($uploadedPhotoPath !== null && $profile instanceof User && Schema::hasColumn('tbl_user', 'avatar')) {
                    $profile->avatar = $uploadedPhotoPath;
                    $profile->save();
                }

                // Keep legacy print_id image path in sync for tables still using old flow.
                $legacyImagePath = $this->storePublicUpload(
                    $request->file('id_photo'),
                    'asset/uploads/print_id/image',
                    $fileKey,
                    8 / 10
                );
                if ($legacyImagePath !== null) {
                    $uploadedPhotoPath = $legacyImagePath;
                }
            }

            if ($request->hasFile('signature')) {
                $uploadedSignPath = $this->storePublicUpload(
                    $request->file('signature'),
                    'asset/uploads/print_id/sign',
                    $fileKey
                );
            }

            if (Schema::hasTable('tbl_printingid_depaide')) {
                $email = $profile?->email ?? $authUser?->email;
                $existing = DB::table('tbl_printingid_depaide')
                    ->where(function ($q) use ($hrid, $email) {
                        $hasAny = false;
                        if ($hrid !== null && $hrid !== '') {
                            $q->where('hr_id', (string) $hrid);
                            $hasAny = true;
                        }
                        if ($email !== null && $email !== '') {
                            if ($hasAny) {
                                $q->orWhere('email', $email);
                            } else {
                                $q->where('email', $email);
                            }
                        }
                    })
                    ->orderByDesc('id')
                    ->first();

                $basePayload = array_filter([
                    'email' => $email,
                    'hr_id' => (string) $hrid,
                    'tin_no' => (string) ($data['tin'] ?? ''),
                    'fname' => (string) ($data['firstname'] ?? ''),
                    'lname' => (string) ($data['lastname'] ?? ''),
                    'mname' => (string) ($data['middlename'] ?? ''),
                    'ext_name' => (string) ($data['extension'] ?? ''),
                    'job_title' => (string) ($data['job_title'] ?? ''),
                    'role' => (string) ($profile?->role ?? ''),
                    'dep_id' => (string) (($profile?->department_id ?? '') ?? ''),
                    'emp_id' => is_numeric($data['employee_id'] ?? null) ? (int) $data['employee_id'] : null,
                    'prc_no' => (string) ($data['prc_no'] ?? ''),
                    'emrgncy_no' => (string) ($data['emergency_contact'] ?? ''),
                    'emrgncy_name' => (string) ($data['emergency_name'] ?? ''),
                    'emrgncy_email' => (string) ($data['emergency_email'] ?? ''),
                    'prfx_name' => (string) ($data['prefix_name'] ?? ''),
                    'bday' => (string) ($data['birth_date'] ?? ''),
                    'gsis_no' => (string) ($data['gsis'] ?? ''),
                    'pagibig_no' => (string) ($data['pag_ibig'] ?? ''),
                    'philhealth_no' => (string) ($data['philhealth'] ?? ''),
                    'blood_type' => (string) ($data['blood_type'] ?? ''),
                ], fn ($v) => $v !== null && $v !== '');

                // Legacy table fallback: update existing row; create minimal row if missing.
                if ($existing) {
                    $updatePayload = $basePayload;
                    if ($uploadedPhotoPath !== null && $uploadedPhotoPath !== '') {
                        $updatePayload['image'] = $uploadedPhotoPath;
                    }
                    if ($uploadedSignPath !== null && $uploadedSignPath !== '') {
                        $updatePayload['sign'] = $uploadedSignPath;
                    }
                    DB::table('tbl_printingid_depaide')->where('id', $existing->id)->update($updatePayload);
                } else {
                    $insert = $basePayload;
                    if ($uploadedPhotoPath !== null && $uploadedPhotoPath !== '') {
                        $insert['image'] = $uploadedPhotoPath;
                    }
                    if ($uploadedSignPath !== null && $uploadedSignPath !== '') {
                        $insert['sign'] = $uploadedSignPath;
                    }
                    DB::table('tbl_printingid_depaide')->insert($insert);
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('ID card file upload step failed', [
                'error' => $e->getMessage(),
                'hrid' => $hrid,
                'email' => $profile?->email ?? $authUser?->email,
            ]);
        }

        return redirect()->route('self-service.id-card')->with('status', 'Details updated.');
    }

    private function storePublicUpload($file, string $relativeDir, string $baseName, ?float $targetAspectRatio = null): ?string
    {
        if (! $file) {
            return null;
        }

        $ext = strtolower((string) $file->getClientOriginalExtension());
        if ($ext === '' || ! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $ext = 'jpg';
        }
        if ($ext === 'webp' && ! function_exists('imagewebp')) {
            $ext = 'jpg';
        }

        $dirPath = public_path(trim($relativeDir, '/'));
        if (! File::exists($dirPath)) {
            File::makeDirectory($dirPath, 0755, true, true);
        }

        $filename = trim($baseName).'.'.$ext;
        $targetPath = $dirPath.DIRECTORY_SEPARATOR.$filename;
        $sourcePath = $file->getRealPath();
        if (! is_string($sourcePath) || ! is_file($sourcePath)) {
            return null;
        }

        if (File::exists($targetPath)) {
            File::delete($targetPath);
        }

        // For ID photo uploads, persist an actual center-cropped image (8:10),
        // not just a CSS preview crop in the UI.
        if ($targetAspectRatio !== null && $targetAspectRatio > 0 && function_exists('imagecreatefromstring')) {
            $raw = @file_get_contents($sourcePath);
            $src = is_string($raw) ? @imagecreatefromstring($raw) : false;
            if ($src !== false) {
                $srcW = imagesx($src);
                $srcH = imagesy($src);
                if ($srcW > 0 && $srcH > 0) {
                    $srcRatio = $srcW / $srcH;
                    if ($srcRatio > $targetAspectRatio) {
                        $cropH = $srcH;
                        $cropW = (int) round($cropH * $targetAspectRatio);
                        $cropX = (int) floor(($srcW - $cropW) / 2);
                        $cropY = 0;
                    } else {
                        $cropW = $srcW;
                        $cropH = (int) round($cropW / $targetAspectRatio);
                        $cropX = 0;
                        $cropY = (int) floor(($srcH - $cropH) / 2);
                    }

                    $dst = imagecreatetruecolor($cropW, $cropH);
                    if ($dst !== false) {
                        // Keep transparency for PNG/WEBP outputs.
                        imagealphablending($dst, false);
                        imagesavealpha($dst, true);
                        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                        imagefilledrectangle($dst, 0, 0, $cropW, $cropH, $transparent);

                        imagecopyresampled(
                            $dst,
                            $src,
                            0,
                            0,
                            $cropX,
                            $cropY,
                            $cropW,
                            $cropH,
                            $cropW,
                            $cropH
                        );

                        $saved = match ($ext) {
                            'png' => imagepng($dst, $targetPath),
                            'webp' => imagewebp($dst, $targetPath, 90),
                            default => imagejpeg($dst, $targetPath, 90),
                        };

                        imagedestroy($dst);
                        imagedestroy($src);

                        if ($saved && is_file($targetPath)) {
                            return trim($relativeDir, '/').'/'.$filename;
                        }
                    }
                }

                imagedestroy($src);
            }
        }

        File::copy($sourcePath, $targetPath);

        return trim($relativeDir, '/').'/'.$filename;
    }

    private function resolveStoredSignaturePath(mixed $hrid, ?string $email): ?string
    {
        if (($hrid !== null && $hrid !== '')) {
            $hridKey = (string) $hrid;
            foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
                $relative = 'asset/uploads/print_id/sign/'.$hridKey.'.'.$ext;
                if (is_file(public_path($relative))) {
                    return $relative;
                }
            }
        }

        if (! Schema::hasTable('tbl_printingid_depaide')) {
            return null;
        }
        if (($hrid === null || $hrid === '') && ($email === null || $email === '')) {
            return null;
        }

        $row = DB::table('tbl_printingid_depaide')
            ->where(function ($q) use ($hrid, $email) {
                $hasAny = false;
                if ($hrid !== null && $hrid !== '') {
                    $q->where('hr_id', (string) $hrid);
                    $hasAny = true;
                }
                if ($email !== null && $email !== '') {
                    if ($hasAny) {
                        $q->orWhere('email', $email);
                    } else {
                        $q->where('email', $email);
                    }
                }
            })
            ->orderByDesc('id')
            ->first();

        $sign = isset($row?->sign) ? trim((string) $row->sign) : '';

        return $sign !== '' ? $sign : null;
    }
}
