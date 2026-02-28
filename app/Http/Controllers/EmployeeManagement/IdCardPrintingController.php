<?php

namespace App\Http\Controllers\EmployeeManagement;

use App\Http\Controllers\Controller;
use App\Models\EmpOfficialInfo;
use App\Models\EmpPersonalInfo;
use App\Models\RequestedId;
use App\Models\User;
use App\Services\IdCardImageService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class IdCardPrintingController extends Controller
{
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
                    'updated_at' => $row->updated_at?->toIso8601String(),
                ])
                ->values()
                ->all();
        }

        return Inertia::render('EmployeeManagement/IdCardPrinting', [
            'requests' => $requests,
        ]);
    }

    /**
     * EODB ID BB: open a new tab with the ID card image from your PNG template.
     * Uses TEMPLATE ID/TEMPLATE (or ID_CARD_TEMPLATES_PATH); overlays user name, ID no, division, and photo.
     */
    public function eodbIdBb(Request $request, int $id): Response
    {
        $requested = RequestedId::query()->findOrFail($id);
        $userId = $requested->user_id ?? null;
        $hrid = $requested->hrid ?? null;

        $profile = null;
        $officialInfo = null;

        if ($userId && Schema::hasTable('tbl_user')) {
            $profile = User::query()
                ->select(['userId', 'hrId', 'email', 'lastname', 'firstname', 'middlename', 'extname', 'avatar', 'job_title', 'role', 'fullname', 'department_id'])
                ->where('userId', $userId)
                ->first();
            if ($profile) {
                $hrid = $profile->hrId ?? $profile->userId ?? $hrid;
            }
        }

        if ($hrid !== null && Schema::hasTable('tbl_emp_official_info')) {
            $officialInfo = EmpOfficialInfo::query()->where('hrid', $hrid)->first();
        }

        $fullname = trim((string) ($requested->fullname ?? ''));
        if ($fullname === '') {
            $fullname = trim(implode(' ', array_filter([
                $officialInfo?->firstname ?? $profile?->firstname ?? '',
                $officialInfo?->middlename ?? $profile?->middlename ?? '',
                $officialInfo?->lastname ?? $profile?->lastname ?? '',
                $officialInfo?->extension ?? $profile?->extname ?? '',
            ])));
        }
        if ($fullname === '') {
            $fullname = $profile?->fullname ?? $profile?->name ?? $requested->email ?? 'Employee';
        }

        $employeeId = (string) ($officialInfo?->employee_id ?? $profile?->hrId ?? $hrid ?? $requested->hrid ?? '');
        $division = $officialInfo?->division_code ?? $officialInfo?->office ?? $profile?->department_id ?? 'DIVISION OFFICE';
        $division = $division ? (string) $division : 'DIVISION OFFICE';

        $employStatus = $officialInfo?->employ_status ?? null;
        $jobTitle = $officialInfo?->job_title ?? $profile?->job_title ?? null;
        $jobShorten = $officialInfo?->job_shorten ?? null;
        if ($jobShorten === null && $jobTitle !== null) {
            $jobShorten = config('id-card.job_title_to_template')[$jobTitle] ?? null;
        }

        $photoPath = null;
        $avatarCandidates = [];
        if ($profile && ! empty($profile->avatar)) {
            $av = trim((string) $profile->avatar);
            if (! str_starts_with($av, 'http') && ! str_starts_with($av, '//')) {
                $avatarCandidates = [
                    Storage::path('public/avatars/'.$av),
                    public_path('storage/avatars/'.$av),
                    public_path('images/'.$av),
                    storage_path('app/public/avatars/'.$av),
                ];
            }
        }
        if ($hrid !== null) {
            $avatarCandidates[] = public_path('uploads/'.$hrid.'/'.$hrid.'.jpg');
            $avatarCandidates[] = base_path('public/uploads/'.$hrid.'/'.$hrid.'.jpg');
            $avatarCandidates[] = Storage::path('public/uploads/'.$hrid.'/'.$hrid.'.jpg');
        }
        foreach ($avatarCandidates as $c) {
            if ($c !== '' && is_file($c)) {
                $photoPath = $c;
                break;
            }
        }

        $role = $profile?->role ?? $officialInfo?->role ?? null;

        $png = IdCardImageService::buildEodbCard([
            'fullname' => $fullname,
            'employee_id' => $employeeId,
            'division' => $division,
            'photo_path' => $photoPath,
            'employ_status' => $employStatus ? (string) $employStatus : null,
            'job_shorten' => $jobShorten ? (string) $jobShorten : null,
            'role' => $role ? (string) $role : null,
        ]);

        if ($png === null) {
            $templateDir = IdCardImageService::templatesPath();
            abort(404, $templateDir === null
                ? 'ID card template folder not found. Set ID_CARD_TEMPLATES_PATH in .env to the full path of your TEMPLATE ID/TEMPLATE folder, or copy template PNGs into public/id-card-templates.'
                : 'No PNG template found in '.$templateDir.'. Add a template (e.g. EODBBB.png) or set ID_CARD_EODB_TEMPLATE in .env.');
        }

        $filename = 'eodb-id-'.preg_replace('/[^a-z0-9\-]/i', '-', $fullname).'.pdf';
        $base64 = base64_encode($png);
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body style="margin:0;padding:0;text-align:center;"><img src="data:image/png;base64,'.$base64.'" style="max-width:100%;height:auto;display:block;margin:0 auto;" /></body></html>';

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

        return $pdf->stream($filename, ['Attachment' => false]);
    }
}
