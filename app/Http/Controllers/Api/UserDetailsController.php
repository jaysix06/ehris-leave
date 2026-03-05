<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserDetailsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $authUser = $request->user();
        $dbProfile = null;

        if ($authUser && Schema::hasTable('tbl_user')) {
            $dbProfile = User::query()
                ->select([
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
        }

        $hrid = $dbProfile?->hrId
            ?? $authUser?->hrId
            ?? $authUser?->id
            ?? null;

        if ($dbProfile === null && $authUser) {
            $dbProfile = (object) [
                'hrId' => $authUser->hrId ?? $authUser->id ?? null,
                'email' => $authUser->email ?? null,
                'lastname' => $authUser->lastname ?? null,
                'firstname' => $authUser->firstname ?? null,
                'middlename' => $authUser->middlename ?? null,
                'extname' => $authUser->extname ?? null,
                'avatar' => $authUser->avatar ?? null,
                'job_title' => $authUser->job_title ?? null,
                'role' => $authUser->role ?? null,
                'fullname' => $authUser->fullname ?? $authUser->name ?? null,
            ];
        }

        $officialInfo = null;
        $personalInfo = null;
        $contactInfo = null;

        if ($hrid !== null) {
            $officialInfo = Schema::hasTable('tbl_emp_official_info')
                ? DB::table('tbl_emp_official_info')->where('hrid', $hrid)->first()
                : null;
            $personalInfo = Schema::hasTable('tbl_emp_personal_info')
                ? DB::table('tbl_emp_personal_info')->where('hrid', $hrid)->first()
                : null;
            $contactInfo = Schema::hasTable('tbl_emp_contact_info')
                ? DB::table('tbl_emp_contact_info as c')
                    ->leftJoin('tbl_barangay as rb', 'rb.barangay_id', '=', 'c.barangay')
                    ->leftJoin('tbl_province as rp', 'rp.province_id', '=', 'c.province')
                    ->leftJoin('tbl_barangay as pb', 'pb.barangay_id', '=', 'c.barangay1')
                    ->leftJoin('tbl_province as pp', 'pp.province_id', '=', 'c.province1')
                    ->where('c.hrid', $hrid)
                    ->select([
                        'c.*',
                        'rb.barangay_name as residential_barangay_name',
                        'rp.province_name as residential_province_name',
                        'pb.barangay_name as permanent_barangay_name',
                        'pp.province_name as permanent_province_name',
                    ])
                    ->first()
                : null;
        }

        return response()->json([
            'data' => [
                'profile' => $dbProfile,
                'officialInfo' => $officialInfo,
                'personalInfo' => $personalInfo,
                'contactInfo' => $contactInfo,
            ],
        ]);
    }
}
