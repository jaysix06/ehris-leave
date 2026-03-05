<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EmployeeDetailsController extends Controller
{
    public function __invoke(Request $request, int $hrid): JsonResponse
    {
        $userDetails = Schema::hasTable('tbl_user')
            ? DB::table('tbl_user')
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
                ->where('hrId', $hrid)
                ->first()
            : null;

        if ($userDetails === null) {
            return response()->json([
                'message' => 'Employee details not found.',
            ], 404);
        }

        return response()->json([
            'data' => [
                'userDetails' => $userDetails,
            ],
        ]);
    }
}
