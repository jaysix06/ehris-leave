<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class PocketIdImageService
{
    /**
     * Build pocket ID spread image:
     * - Left panel: generated front card (same pipeline as EODB BB)
     * - Right panel: POCKET.png template overlaid on the 2nd card
     */
    public static function buildPocketSpread(array $ctx): ?string
    {
        $superintendent = self::resolveSuperintendentData();

        return IdCardImageService::buildEodbCard([
            'fullname' => $ctx['fullname'] ?? '',
            'lastname' => $ctx['lastname'] ?? '',
            'firstname' => $ctx['firstname'] ?? '',
            'middlename' => $ctx['middlename'] ?? '',
            'extension' => $ctx['extension'] ?? '',
            'employee_id' => $ctx['employee_id'] ?? '',
            'department_abbrev' => $ctx['department_abbrev'] ?? '',
            'division' => $ctx['division'] ?? '',
            'photo_path' => $ctx['photo_path'] ?? null,
            'signature_path' => $ctx['signature_path'] ?? null,
            'employ_status' => $ctx['employ_status'] ?? null,
            'job_shorten' => $ctx['job_shorten'] ?? null,
            'job_title' => $ctx['job_title'] ?? null,
            'role' => $ctx['role'] ?? null,
            'pocket_overlay_path' => self::resolvePocketTemplatePath(),
            'pocket_back_logo_path' => self::resolvePocketBackLogoPath(),
            'pocket_back_fields' => [
                'superintendent_name' => (string) ($superintendent['name'] ?? ''),
                'superintendent_job_title' => (string) ($superintendent['job_title'] ?? ''),
                'superintendent_signature_path' => (string) ($superintendent['signature_path'] ?? ''),
                'emergency_contact' => (string) ($ctx['emergency_contact'] ?? ''),
                'station_no' => (string) ($ctx['station_no'] ?? ''),
                'tin' => (string) ($ctx['tin'] ?? ''),
                'gsis' => (string) ($ctx['gsis'] ?? ''),
                'pag_ibig' => (string) ($ctx['pag_ibig'] ?? ''),
                'philhealth' => (string) ($ctx['philhealth'] ?? ''),
                'birth_date' => (string) ($ctx['birth_date'] ?? ''),
                'blood_type' => (string) ($ctx['blood_type'] ?? ''),
            ],
        ]);
    }

    private static function resolvePocketTemplatePath(): ?string
    {
        $dir = IdCardImageService::templatesPath();
        if ($dir === null) {
            return null;
        }

        foreach (['POCKET.png', 'pocket.png', 'POCKET.PNG'] as $name) {
            $path = $dir.DIRECTORY_SEPARATOR.$name;
            if (File::isFile($path)) {
                return $path;
            }
        }

        return null;
    }

    private static function resolvePocketBackLogoPath(): ?string
    {
        $path = public_path('depedozamiz.png');

        return File::isFile($path) ? $path : null;
    }

    /**
     * @return array{name:string,job_title:string,signature_path:string}
     */
    private static function resolveSuperintendentData(): array
    {
        if (! Schema::hasTable('tbl_user')) {
            return ['name' => '', 'job_title' => '', 'signature_path' => ''];
        }

        $row = DB::table('tbl_user')
            ->select(['fullname', 'firstname', 'middlename', 'lastname', 'extname', 'job_title', 'userId', 'hrId'])
            ->where('job_title', 'Schools Division Superintendent')
            ->orderByDesc('userId')
            ->first();

        // Fallback: case-insensitive and spacing-tolerant match.
        if (! $row) {
            $rows = DB::table('tbl_user')
                ->select(['fullname', 'firstname', 'middlename', 'lastname', 'extname', 'job_title', 'userId', 'hrId'])
                ->whereNotNull('job_title')
                ->orderByDesc('userId')
                ->get();

            $target = preg_replace('/\s+/', ' ', strtolower('Schools Division Superintendent'));
            foreach ($rows as $candidate) {
                $jt = preg_replace('/\s+/', ' ', strtolower(trim((string) ($candidate->job_title ?? ''))));
                if ($jt === $target) {
                    $row = $candidate;
                    break;
                }
            }
        }

        if (! $row) {
            return ['name' => '', 'job_title' => '', 'signature_path' => ''];
        }

        $full = trim((string) ($row->fullname ?? ''));
        if ($full === '') {
            $parts = array_filter([
                $row->firstname ?? null,
                $row->middlename ?? null,
                $row->lastname ?? null,
                $row->extname ?? null,
            ], fn ($v) => is_string($v) && trim($v) !== '');
            $full = trim(implode(' ', $parts));
        }

        return [
            'name' => $full,
            'job_title' => trim((string) ($row->job_title ?? 'Schools Division Superintendent')),
            'signature_path' => self::resolveSuperintendentSignaturePath($row),
        ];
    }

    private static function resolveSuperintendentSignaturePath(object $row): string
    {
        $keys = [];
        $userId = isset($row->userId) ? trim((string) $row->userId) : '';
        $hrId = isset($row->hrId) ? trim((string) $row->hrId) : '';
        if ($userId !== '') {
            $keys[] = $userId;
        }
        if ($hrId !== '' && ! in_array($hrId, $keys, true)) {
            $keys[] = $hrId;
        }

        foreach ($keys as $key) {
            foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
                $path = public_path('asset/uploads/print_id/sign/'.$key.'.'.$ext);
                if (is_file($path)) {
                    return $path;
                }
            }
        }

        return '';
    }
}
