<?php

namespace App\Http\Controllers\EmployeeManagement;

use App\Http\Controllers\Controller;
use App\Models\EmpOfficialInfo;
use App\Models\EmpPersonalInfo;
use App\Models\RequestedId;
use App\Models\User;
use App\Services\IdCardImageService;
use App\Services\PocketIdImageService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class IdCardPrintingController extends Controller
{
    private const CARD_OPTION_POCKET_ID = 'pocket_id';

    private const CARD_OPTION_EODB_ID_BB = 'eodb_id_bb';

    /**
     * List requested IDs for the Employee Management → ID Card Printing page.
     */
    public function index(Request $request)
    {
        $requests = [];

        if (Schema::hasTable('tbl_requested_id')) {
            $requests = RequestedId::query()
                ->orderByDesc('updated_at')
                ->get()
                ->map(fn ($row) => [
                    'id' => $row->id,
                    'hrid' => $row->hrid,
                    'user_id' => $row->user_id,
                    'fullname' => $row->fullname ?? '—',
                    'email' => $row->email,
                    'status' => $row->status ?? 'On Process',
                    'card_option' => $this->normalizeCardOption($row->card_option ?? null),
                    'updated_at' => $row->updated_at?->toIso8601String(),
                ])
                ->values()
                ->all();
        } elseif (Schema::hasTable('tbl_printingid_depaide')) {
            $requests = DB::table('tbl_printingid_depaide')
                ->orderByDesc('id')
                ->get()
                ->map(fn ($row) => [
                    'id' => (int) $row->id,
                    'hrid' => isset($row->hr_id) && is_numeric($row->hr_id) ? (int) $row->hr_id : null,
                    'user_id' => null,
                    'fullname' => trim(implode(' ', array_filter([
                        $row->fname ?? null,
                        $row->mname ?? null,
                        $row->lname ?? null,
                        $row->ext_name ?? null,
                    ]))) ?: ($row->email ?? '—'),
                    'email' => $row->email ?? null,
                    'status' => 'On Process',
                    'card_option' => self::CARD_OPTION_EODB_ID_BB,
                    'updated_at' => null,
                ])
                ->values()
                ->all();
        }

        return Inertia::render('EmployeeManagement/IdCardPrinting', [
            'requests' => $requests,
        ]);
    }

    /**
     * Print using the card option selected in Self Service.
     */
    public function print(Request $request, int $id): Response
    {
        $ctx = $this->resolvePrintContext($id);
        if (($ctx['card_option'] ?? self::CARD_OPTION_EODB_ID_BB) === self::CARD_OPTION_POCKET_ID) {
            return $this->renderPocketIdPdf($ctx);
        }

        return $this->renderEodbIdBbPdf($ctx);
    }

    public function eodbId(Request $request, int $id): Response
    {
        $ctx = $this->resolvePrintContext($id);

        return $this->renderPocketIdPdf($ctx);
    }

    /**
     * EODB ID BB: open a new tab with the ID card image from your PNG template.
     */
    public function eodbIdBb(Request $request, int $id): Response
    {
        $ctx = $this->resolvePrintContext($id);

        return $this->renderEodbIdBbPdf($ctx);
    }

    private function renderEodbIdBbPdf(array $ctx): Response
    {
        $png = IdCardImageService::buildEodbCard([
            'fullname' => $ctx['fullname'],
            'lastname' => $ctx['lastname'],
            'firstname' => $ctx['firstname'],
            'middlename' => $ctx['middlename'],
            'extension' => $ctx['extension'],
            'employee_id' => $ctx['employee_id'],
            'department_abbrev' => $ctx['department_abbrev'],
            'division' => $ctx['division'],
            'photo_path' => $ctx['photo_path'],
            'signature_path' => $ctx['signature_path'],
            'employ_status' => $ctx['employ_status'],
            'job_shorten' => $ctx['job_shorten'],
            'job_title' => $ctx['job_title'],
            'role' => $ctx['role'],
        ]);

        if ($png === null) {
            $templateDir = IdCardImageService::templatesPath();
            abort(404, $templateDir === null
                ? 'ID card template folder not found. Set ID_CARD_TEMPLATES_PATH in .env to the full path of your TEMPLATE ID/TEMPLATE folder, or copy template PNGs into public/id-card-templates.'
                : 'No PNG template found in '.$templateDir.'. Add a template (e.g. EODBBB.png) or set ID_CARD_EODB_TEMPLATE in .env.');
        }

        $filename = 'eodb-id-bb-'.preg_replace('/[^a-z0-9\-]/i', '-', $ctx['fullname']).'.pdf';
        $pdf = Pdf::loadView('id-cards.eodb-id-bb', [
            'card_image_data_uri' => 'data:image/png;base64,'.base64_encode($png),
        ])->setPaper('a4', 'portrait');

        $response = $pdf->stream($filename, ['Attachment' => false]);
        if (app()->environment('testing')) {
            $response->headers->set('X-Ehris-Card-Option', self::CARD_OPTION_EODB_ID_BB);
        }

        return $response;
    }

    private function renderPocketIdPdf(array $ctx): Response
    {
        $spreadPng = PocketIdImageService::buildPocketSpread($ctx);
        if ($spreadPng === null) {
            abort(404, 'Unable to generate pocket ID image. Check template files and GD extension.');
        }

        $pdf = Pdf::loadView('id-cards.eodb-id-bb', [
            'card_image_data_uri' => 'data:image/png;base64,'.base64_encode($spreadPng),
        ])->setPaper('a4', 'portrait');

        $filename = 'eodb-id-'.preg_replace('/[^a-z0-9\-]/i', '-', $ctx['fullname']).'.pdf';

        $response = $pdf->stream($filename, ['Attachment' => false]);
        if (app()->environment('testing')) {
            $response->headers->set('X-Ehris-Card-Option', self::CARD_OPTION_POCKET_ID);
        }

        return $response;
    }

    private function resolvePrintContext(int $id): array
    {
        $requested = null;
        $legacyById = null;
        if (Schema::hasTable('tbl_requested_id')) {
            $requested = RequestedId::query()->find($id);
        }
        if (! $requested && Schema::hasTable('tbl_printingid_depaide')) {
            $legacyById = DB::table('tbl_printingid_depaide')->where('id', $id)->first();
        }
        if (! $requested && ! $legacyById) {
            abort(404, 'Requested ID record not found.');
        }

        $userId = $requested?->user_id ?? null;
        $hrid = $requested?->hrid ?? (isset($legacyById?->hr_id) && is_numeric($legacyById->hr_id) ? (int) $legacyById->hr_id : null);
        $email = $requested?->email ?? ($legacyById?->email ?? null);

        $profile = null;
        if (Schema::hasTable('tbl_user')) {
            $userQuery = User::query()
                ->select(['userId', 'hrId', 'email', 'lastname', 'firstname', 'middlename', 'extname', 'avatar', 'job_title', 'role', 'fullname', 'department_id']);
            if ($userId) {
                $profile = (clone $userQuery)->where('userId', $userId)->first();
            }
            if (! $profile && $email) {
                $profile = (clone $userQuery)->where('email', $email)->first();
            }
            if ($profile) {
                $hrid = $profile->hrId ?? $profile->userId ?? $hrid;
                $email = $profile->email ?? $email;
            }
        }

        $officialInfo = null;
        if ($hrid !== null && Schema::hasTable('tbl_emp_official_info')) {
            $officialInfo = EmpOfficialInfo::query()->where('hrid', $hrid)->first();
        }
        $personalInfo = null;
        if ($hrid !== null && Schema::hasTable('tbl_emp_personal_info')) {
            $personalInfo = EmpPersonalInfo::query()->where('hrid', $hrid)->first();
        }

        $legacyPrintRow = $this->resolveLegacyPrintIdRow($hrid, $email) ?? $legacyById;
        $cardOption = $this->normalizeCardOption($requested?->card_option ?? null);

        $fullname = trim((string) ($requested?->fullname ?? ''));
        if ($fullname === '') {
            $fullname = trim(implode(' ', array_filter([
                $officialInfo?->firstname ?? $profile?->firstname ?? $legacyPrintRow?->fname ?? '',
                $officialInfo?->middlename ?? $profile?->middlename ?? $legacyPrintRow?->mname ?? '',
                $officialInfo?->lastname ?? $profile?->lastname ?? $legacyPrintRow?->lname ?? '',
                $officialInfo?->extension ?? $profile?->extname ?? $legacyPrintRow?->ext_name ?? '',
            ])));
        }
        if ($fullname === '') {
            $fullname = $profile?->fullname ?? $email ?? 'Employee';
        }

        $employeeId = (string) ($officialInfo?->employee_id ?? $legacyPrintRow?->emp_id ?? $profile?->hrId ?? $hrid ?? '');
        $division = $officialInfo?->division_code ?? $officialInfo?->office ?? $legacyPrintRow?->dep_id ?? $profile?->department_id ?? 'DIVISION OFFICE';
        $division = $division ? (string) $division : 'DIVISION OFFICE';
        $departmentAbbrev = (string) ($officialInfo?->department_abbrev ?? '');
        if ($departmentAbbrev === '') {
            $departmentId = $officialInfo?->department_id ?? $profile?->department_id ?? $legacyPrintRow?->dep_id ?? null;
            if (
                $departmentId !== null
                && Schema::hasTable('tbl_department')
                && Schema::hasColumn('tbl_department', 'department_abbrev')
            ) {
                $deptQuery = DB::table('tbl_department')->select('department_abbrev');
                if (Schema::hasColumn('tbl_department', 'department_id')) {
                    $deptQuery->where('department_id', (string) $departmentId);
                } else {
                    $deptQuery->where('id', (string) $departmentId);
                }
                $departmentAbbrev = (string) ($deptQuery->value('department_abbrev') ?? '');
            }
        }
        $role = (string) ($profile?->role ?? $officialInfo?->role ?? $legacyPrintRow?->role ?? '');

        $employStatus = $officialInfo?->employ_status ?? null;
        $jobTitle = $officialInfo?->job_title ?? $profile?->job_title ?? $legacyPrintRow?->job_title ?? null;
        $jobShorten = $officialInfo?->job_shorten ?? null;
        if ($jobShorten === null && $jobTitle !== null) {
            if (
                Schema::hasTable('tbl_job_title')
                && Schema::hasColumn('tbl_job_title', 'job_title')
                && Schema::hasColumn('tbl_job_title', 'job_shorten')
            ) {
                $jobShorten = DB::table('tbl_job_title')
                    ->where('job_title', $jobTitle)
                    ->value('job_shorten');

                // Case-insensitive fallback if exact job_title match is missing.
                if ($jobShorten === null || trim((string) $jobShorten) === '') {
                    $rows = DB::table('tbl_job_title')
                        ->select(['job_title', 'job_shorten'])
                        ->whereNotNull('job_title')
                        ->whereNotNull('job_shorten')
                        ->get();
                    foreach ($rows as $row) {
                        if (strcasecmp((string) ($row->job_title ?? ''), (string) $jobTitle) === 0) {
                            $jobShorten = $row->job_shorten ?? null;
                            break;
                        }
                    }
                }
            }

            if ($jobShorten === null || trim((string) $jobShorten) === '') {
                $jobShorten = config('id-card.job_title_to_template')[$jobTitle] ?? null;
            }
        }

        $photoPath = $this->resolveStoredAssetPath($profile?->avatar);
        if ($photoPath === null && $legacyPrintRow && isset($legacyPrintRow->image)) {
            $photoPath = $this->resolveStoredAssetPath($legacyPrintRow->image);
        }
        if ($photoPath === null && $hrid !== null) {
            foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
                $candidate = public_path('uploads/'.$hrid.'/'.$hrid.'.'.$ext);
                if (is_file($candidate)) {
                    $photoPath = $candidate;
                    break;
                }
            }
        }

        $signaturePath = $legacyPrintRow ? $this->resolveStoredAssetPath($legacyPrintRow->sign ?? null) : null;
        if ($signaturePath === null) {
            $fileKey = (string) ($profile?->userId ?? $hrid ?? '');
            if ($fileKey !== '') {
                foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
                    $candidate = public_path('asset/uploads/print_id/sign/'.$fileKey.'.'.$ext);
                    if (is_file($candidate)) {
                        $signaturePath = $candidate;
                        break;
                    }
                }
            }
        }

        return [
            'fullname' => $fullname,
            'lastname' => (string) ($officialInfo?->lastname ?? $profile?->lastname ?? $legacyPrintRow?->lname ?? ''),
            'firstname' => (string) ($officialInfo?->firstname ?? $profile?->firstname ?? $legacyPrintRow?->fname ?? ''),
            'middlename' => (string) ($officialInfo?->middlename ?? $profile?->middlename ?? $legacyPrintRow?->mname ?? ''),
            'extension' => (string) ($officialInfo?->extension ?? $profile?->extname ?? $legacyPrintRow?->ext_name ?? ''),
            'employee_id' => $employeeId,
            'department_abbrev' => $departmentAbbrev,
            'division' => $division,
            'employ_status' => $employStatus ? (string) $employStatus : null,
            'job_shorten' => $jobShorten ? (string) $jobShorten : null,
            'job_title' => $jobTitle ? (string) $jobTitle : null,
            'role' => $role !== '' ? $role : null,
            'photo_path' => $photoPath,
            'signature_path' => $signaturePath,
            'emergency_name' => (string) ($officialInfo?->emergency_name ?? $legacyPrintRow?->emrgncy_name ?? ''),
            'emergency_contact' => (string) ($officialInfo?->emergency_num ?? $legacyPrintRow?->emrgncy_no ?? ''),
            'station_no' => (string) ($officialInfo?->station_no ?? $officialInfo?->station_code ?? $legacyPrintRow?->station_no ?? $legacyPrintRow?->station_code ?? ''),
            'tin' => (string) ($personalInfo?->tin ?? $legacyPrintRow?->tin_no ?? ''),
            'gsis' => (string) ($personalInfo?->gsis ?? $legacyPrintRow?->gsis_no ?? ''),
            'pag_ibig' => (string) ($personalInfo?->pag_ibig ?? $legacyPrintRow?->pagibig_no ?? ''),
            'philhealth' => (string) ($personalInfo?->philhealth ?? $legacyPrintRow?->philhealth_no ?? ''),
            'birth_date' => (string) ($personalInfo?->dob ?? $legacyPrintRow?->bday ?? ''),
            'blood_type' => (string) ($personalInfo?->blood_type ?? $legacyPrintRow?->blood_type ?? ''),
            'card_option' => $cardOption,
        ];
    }

    private function normalizeCardOption(mixed $value): string
    {
        $option = trim((string) $value);
        if (in_array($option, [self::CARD_OPTION_POCKET_ID, self::CARD_OPTION_EODB_ID_BB], true)) {
            return $option;
        }

        return self::CARD_OPTION_EODB_ID_BB;
    }

    private function resolveLegacyPrintIdRow(mixed $hrid, ?string $email): ?object
    {
        if (! Schema::hasTable('tbl_printingid_depaide')) {
            return null;
        }
        if (($hrid === null || $hrid === '') && ($email === null || $email === '')) {
            return null;
        }

        return DB::table('tbl_printingid_depaide')
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
    }

    private function resolveStoredAssetPath(?string $value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '' || str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://') || str_starts_with($raw, '//')) {
            return null;
        }

        $normalized = ltrim(parse_url($raw, PHP_URL_PATH) ?? $raw, '/');
        if ($normalized === '') {
            return null;
        }

        $candidates = [
            public_path($normalized),
            base_path('public/'.$normalized),
            storage_path('app/public/'.$normalized),
        ];

        // Legacy avatar values that only store filename (e.g., "4772612.jpg")
        if (! str_contains($normalized, '/')) {
            $candidates[] = Storage::path('public/avatars/'.$normalized);
            $candidates[] = public_path('storage/avatars/'.$normalized);
            $candidates[] = public_path('images/'.$normalized);
            $candidates[] = storage_path('app/public/avatars/'.$normalized);
        }

        foreach ($candidates as $candidate) {
            if ($candidate !== '' && is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function toDataUri(?string $path): ?string
    {
        if (! $path || ! is_file($path)) {
            return null;
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'image/png',
        };

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
    }
}
