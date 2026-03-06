<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EmployeeDetailsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! Schema::hasTable('tbl_user')) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'count' => 0,
                ],
            ]);
        }

        $users = DB::table('tbl_user')
            ->select($this->userDetailsFields())
            ->orderBy('hrId')
            ->get()
            ->map(function ($row) {
                return [
                    'userDetails' => $row,
                ];
            })
            ->values();

        return response()->json([
            'data' => $users,
            'meta' => [
                'count' => $users->count(),
            ],
        ]);
    }

    public function __invoke(Request $request, int $hrid): JsonResponse
    {
        $userDetails = Schema::hasTable('tbl_user')
            ? DB::table('tbl_user')
                ->select($this->userDetailsFields())
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

    /**
     * @return array<int, string>
     */
    private function userDetailsFields(): array
    {
        return [
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
        ];
    }
}
