<?php

namespace App\Http\Controllers\SelfService;

use App\Http\Controllers\Controller;
use App\Models\EmpOfficialInfo;
use App\Models\EmpPersonalInfo;
use App\Models\RequestedId;
use App\Models\User;
use App\Services\IdCardImageService;
use App\Services\PocketIdImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IdCardController extends Controller
{
    private const CARD_OPTION_POCKET_ID = 'pocket_id';

    private const CARD_OPTION_EODB_ID_BB = 'eodb_id_bb';

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
        $selectedCardOption = self::CARD_OPTION_EODB_ID_BB;
        $existingRequest = $this->findRequestedIdRecord($profile, $hrid, $email);
        if ($existingRequest && $this->isCardOptionValid((string) ($existingRequest->card_option ?? ''))) {
            $selectedCardOption = (string) $existingRequest->card_option;
        }

        return Inertia::render('SelfService/IdCard', [
            'profile' => $profile,
            'officialInfo' => $officialInfo,
            'personalInfo' => $personalInfo,
            'contactInfo' => $contactInfo,
            'templates' => $templates,
            'templateBaseUrl' => $templateBaseUrl,
            'signaturePath' => $signaturePath,
            'cardOptions' => $this->cardOptions(),
            'selectedCardOption' => $selectedCardOption,
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
     * Render an actual generated sample preview for each card option.
     */
    public function sample(Request $request, string $option): Response
    {
        $cardOption = strtolower(trim($option));
        if (! $this->isCardOptionValid($cardOption)) {
            abort(404, 'Invalid card option.');
        }

        $authUser = $request->user();
        $profile = $authUser && Schema::hasTable('tbl_user')
            ? User::query()
                ->select(['firstname', 'middlename', 'lastname', 'extname', 'email', 'job_title', 'role', 'fullname', 'hrId', 'userId', 'department_id'])
                ->where('email', $authUser->email)
                ->first()
            : null;

        $sampleFirstName = trim((string) ($profile?->firstname ?? 'JUAN'));
        $sampleMiddleName = trim((string) ($profile?->middlename ?? 'SANTOS'));
        $sampleLastName = trim((string) ($profile?->lastname ?? 'DELA CRUZ'));
        $sampleExtension = trim((string) ($profile?->extname ?? ''));
        $sampleFullName = trim((string) ($profile?->fullname ?? ''));
        if ($sampleFullName === '') {
            $sampleFullName = trim(implode(' ', array_filter([$sampleFirstName, $sampleMiddleName, $sampleLastName, $sampleExtension])));
        }

        $samplePhotoPath = is_file(public_path('avatar-default.jpg')) ? public_path('avatar-default.jpg') : null;
        $sampleSignaturePath = null;
        $signatureRelativePath = $this->resolveStoredSignaturePath(
            $profile?->hrId ?? $profile?->userId ?? null,
            $profile?->email
        );
        if ($signatureRelativePath !== null) {
            $candidate = public_path(ltrim($signatureRelativePath, '/'));
            if (is_file($candidate)) {
                $sampleSignaturePath = $candidate;
            }
        }

        $ctx = [
            'fullname' => $sampleFullName,
            'lastname' => $sampleLastName,
            'firstname' => $sampleFirstName,
            'middlename' => $sampleMiddleName,
            'extension' => $sampleExtension,
            'employee_id' => '000000',
            'department_abbrev' => 'DEPED OZAMIZ',
            'division' => 'DIVISION OFFICE',
            'photo_path' => $samplePhotoPath,
            'signature_path' => $sampleSignaturePath,
            'employ_status' => 'Permanent',
            'job_shorten' => null,
            'job_title' => (string) ($profile?->job_title ?? 'Teacher I'),
            'role' => (string) ($profile?->role ?? ''),
            'emergency_contact' => '09171234567',
            'station_no' => '0000',
            'tin' => '000-000-000',
            'gsis' => '0000000000',
            'pag_ibig' => '0000000000',
            'philhealth' => '000000000000',
            'birth_date' => '1990-01-01',
            'blood_type' => 'O+',
        ];

        $png = $cardOption === self::CARD_OPTION_POCKET_ID
            ? PocketIdImageService::buildPocketSpread($ctx)
            : IdCardImageService::buildEodbCard($ctx);

        if ($png === null) {
            abort(404, 'Unable to render sample card preview.');
        }

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    /**
     * Submit ID request using existing MyDetails data and selected card option.
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

        $hrid = $profile?->hrId ?? $profile?->userId ?? $currentKeyHrid;
        $email = $profile?->email ?? $authUser?->email;

        $officialInfo = null;
        if ($hrid !== null && Schema::hasTable('tbl_emp_official_info')) {
            $officialInfo = EmpOfficialInfo::query()->where('hrid', $hrid)->first();
        }
        if ($officialInfo === null && $email && Schema::hasTable('tbl_emp_official_info') && Schema::hasColumn('tbl_emp_official_info', 'email')) {
            $officialInfo = EmpOfficialInfo::query()->where('email', $email)->first();
        }

        $data = $request->validate([
            'card_option' => ['required', 'string', 'in:'.self::CARD_OPTION_POCKET_ID.','.self::CARD_OPTION_EODB_ID_BB],
            'id_photo' => ['required', 'file', 'image', 'max:10240'],
            'signature' => ['required', 'file', 'image', 'max:10240'],
            'emergency_contact' => [$request->input('card_option') === self::CARD_OPTION_POCKET_ID ? 'required' : 'nullable', 'string', 'max:64'],
            'station_no' => [$request->input('card_option') === self::CARD_OPTION_POCKET_ID ? 'required' : 'nullable', 'string', 'max:64'],
            'tin' => [$request->input('card_option') === self::CARD_OPTION_POCKET_ID ? 'required' : 'nullable', 'string', 'max:64'],
            'gsis' => [$request->input('card_option') === self::CARD_OPTION_POCKET_ID ? 'required' : 'nullable', 'string', 'max:64'],
            'pag_ibig' => [$request->input('card_option') === self::CARD_OPTION_POCKET_ID ? 'required' : 'nullable', 'string', 'max:64'],
            'philhealth' => [$request->input('card_option') === self::CARD_OPTION_POCKET_ID ? 'required' : 'nullable', 'string', 'max:64'],
            'birth_date' => [$request->input('card_option') === self::CARD_OPTION_POCKET_ID ? 'required' : 'nullable', 'date'],
            'blood_type' => [$request->input('card_option') === self::CARD_OPTION_POCKET_ID ? 'required' : 'nullable', 'string', 'max:16'],
        ]);

        $personalInfo = null;
        if ($hrid !== null && Schema::hasTable('tbl_emp_personal_info')) {
            $personalInfo = EmpPersonalInfo::query()->where('hrid', $hrid)->first();
        }

        $contactInfo = null;
        if (Schema::hasTable('tbl_emp_contact_info')) {
            if ($hrid !== null) {
                $contactInfo = DB::table('tbl_emp_contact_info')->where('hrid', $hrid)->first();
            }

            if ($contactInfo === null && $email && Schema::hasColumn('tbl_emp_contact_info', 'email')) {
                $contactInfo = DB::table('tbl_emp_contact_info')->where('email', $email)->first();
            }
        }

        $fullname = trim(implode(' ', array_filter([
            (string) ($officialInfo?->firstname ?? $profile?->firstname ?? ''),
            (string) ($officialInfo?->middlename ?? $profile?->middlename ?? ''),
            (string) ($officialInfo?->lastname ?? $profile?->lastname ?? ''),
            (string) ($officialInfo?->extension ?? $profile?->extname ?? ''),
        ])));

        if ($fullname === '' && $profile) {
            $fullname = trim((string) ($profile->fullname ?? $profile->name ?? ''));
        }

        if (Schema::hasTable('tbl_requested_id')) {
            $userId = $profile?->userId ?? $authUser?->userId ?? $authUser?->id ?? null;
            $payload = [
                'hrid' => is_numeric((string) $hrid) ? (int) $hrid : null,
                'fullname' => $fullname !== '' ? $fullname : null,
                'email' => $email,
                'status' => 'On Process',
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('tbl_requested_id', 'card_option')) {
                $payload['card_option'] = (string) $data['card_option'];
            }

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
        $emergencyContactInput = trim((string) ($data['emergency_contact'] ?? ''));
        $stationNoInput = trim((string) ($data['station_no'] ?? ''));
        $tinInput = trim((string) ($data['tin'] ?? ''));
        $gsisInput = trim((string) ($data['gsis'] ?? ''));
        $pagIbigInput = trim((string) ($data['pag_ibig'] ?? ''));
        $philhealthInput = trim((string) ($data['philhealth'] ?? ''));
        $birthDateInput = trim((string) ($data['birth_date'] ?? ''));
        $bloodTypeInput = trim((string) ($data['blood_type'] ?? ''));

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
                    'hr_id' => $hrid !== null ? (string) $hrid : null,
                    'tin_no' => $tinInput !== '' ? $tinInput : (string) ($personalInfo?->tin ?? ''),
                    'fname' => (string) ($officialInfo?->firstname ?? $profile?->firstname ?? ''),
                    'lname' => (string) ($officialInfo?->lastname ?? $profile?->lastname ?? ''),
                    'mname' => (string) ($officialInfo?->middlename ?? $profile?->middlename ?? ''),
                    'ext_name' => (string) ($officialInfo?->extension ?? $profile?->extname ?? ''),
                    'job_title' => (string) ($officialInfo?->job_title ?? $profile?->job_title ?? ''),
                    'role' => (string) ($profile?->role ?? $officialInfo?->role ?? ''),
                    'dep_id' => (string) ($officialInfo?->division_code ?? $officialInfo?->office ?? $profile?->department_id ?? ''),
                    'emp_id' => is_numeric((string) ($officialInfo?->employee_id ?? null))
                        ? (int) $officialInfo?->employee_id
                        : (is_numeric((string) $hrid) ? (int) $hrid : null),
                    'prc_no' => (string) ($personalInfo?->prc_no ?? ''),
                    'emrgncy_no' => $emergencyContactInput !== '' ? $emergencyContactInput : (string) ($contactInfo?->emergency_num ?? ''),
                    'emrgncy_name' => (string) ($contactInfo?->emergency_name ?? ''),
                    'emrgncy_email' => (string) ($contactInfo?->emergency_email ?? ''),
                    'prfx_name' => (string) ($officialInfo?->prefix_name ?? ''),
                    'bday' => $birthDateInput !== '' ? $birthDateInput : (string) ($personalInfo?->dob ?? ''),
                    'gsis_no' => $gsisInput !== '' ? $gsisInput : (string) ($personalInfo?->gsis ?? ''),
                    'pagibig_no' => $pagIbigInput !== '' ? $pagIbigInput : (string) ($personalInfo?->pag_ibig ?? ''),
                    'philhealth_no' => $philhealthInput !== '' ? $philhealthInput : (string) ($personalInfo?->philhealth ?? ''),
                    'blood_type' => $bloodTypeInput !== '' ? $bloodTypeInput : (string) ($personalInfo?->blood_type ?? ''),
                ], fn ($v) => $v !== null && $v !== '');

                $stationNoValue = $stationNoInput !== ''
                    ? $stationNoInput
                    : (string) ($officialInfo?->station_no ?? $officialInfo?->station_code ?? '');
                if ($stationNoValue !== '') {
                    if (Schema::hasColumn('tbl_printingid_depaide', 'station_no')) {
                        $basePayload['station_no'] = $stationNoValue;
                    } elseif (Schema::hasColumn('tbl_printingid_depaide', 'station_code')) {
                        $basePayload['station_code'] = $stationNoValue;
                    }
                }

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
            Log::warning('ID card file upload step failed', [
                'error' => $e->getMessage(),
                'hrid' => $hrid,
                'email' => $email,
            ]);
        }

        return redirect()
            ->route('self-service.id-card')
            ->with('success', 'New submit request sent successfully.');
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

    /**
     * @return array<int, array{value: string, label: string, description: string, sampleImage: string}>
     */
    private function cardOptions(): array
    {
        return [
            [
                'value' => self::CARD_OPTION_POCKET_ID,
                'label' => 'ID only',
                'description' => 'Compact layout for pocket-size printing.',
                'sampleImage' => route('self-service.id-card.sample', ['option' => self::CARD_OPTION_POCKET_ID]),
            ],
            [
                'value' => self::CARD_OPTION_EODB_ID_BB,
                'label' => 'EODB ID BB',
                'description' => 'Standard EODB ID BB layout.',
                'sampleImage' => route('self-service.id-card.sample', ['option' => self::CARD_OPTION_EODB_ID_BB]),
            ],
        ];
    }

    private function isCardOptionValid(string $option): bool
    {
        return in_array($option, [self::CARD_OPTION_POCKET_ID, self::CARD_OPTION_EODB_ID_BB], true);
    }

    private function findRequestedIdRecord(?User $profile, mixed $hrid, ?string $email): ?RequestedId
    {
        if (! Schema::hasTable('tbl_requested_id')) {
            return null;
        }

        $userId = $profile?->userId;
        if ($userId !== null) {
            $record = RequestedId::query()->where('user_id', $userId)->first();
            if ($record) {
                return $record;
            }
        }

        if ($hrid !== null && $hrid !== '') {
            $record = RequestedId::query()->where('hrid', $hrid)->first();
            if ($record) {
                return $record;
            }
        }

        if ($email !== null && $email !== '') {
            return RequestedId::query()->where('email', $email)->first();
        }

        return null;
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
