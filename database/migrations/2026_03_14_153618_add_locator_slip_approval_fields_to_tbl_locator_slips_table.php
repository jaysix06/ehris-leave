<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tbl_locator_slips', function (Blueprint $table) {
            if (! Schema::hasColumn('tbl_locator_slips', 'rm_assignee_hrid')) {
                $table->unsignedBigInteger('rm_assignee_hrid')->nullable()->after('user_id')->index();
            }

            if (! Schema::hasColumn('tbl_locator_slips', 'workflow_status')) {
                $table->string('workflow_status')->default('pending_rm')->after('destination');
            }

            if (! Schema::hasColumn('tbl_locator_slips', 'rm_status')) {
                $table->string('rm_status')->default('pending')->after('workflow_status');
            }

            if (! Schema::hasColumn('tbl_locator_slips', 'rm_acted_by')) {
                $table->unsignedBigInteger('rm_acted_by')->nullable()->after('rm_status');
            }

            if (! Schema::hasColumn('tbl_locator_slips', 'rm_action_at')) {
                $table->timestamp('rm_action_at')->nullable()->after('rm_acted_by');
            }

            if (! Schema::hasColumn('tbl_locator_slips', 'rm_remarks')) {
                $table->text('rm_remarks')->nullable()->after('rm_action_at');
            }
        });

        DB::table('tbl_locator_slips')
            ->where(function ($query) {
                $query->whereNull('workflow_status')
                    ->orWhere('workflow_status', '');
            })
            ->update(['workflow_status' => 'pending_rm']);

        DB::table('tbl_locator_slips')
            ->where(function ($query) {
                $query->whereNull('rm_status')
                    ->orWhere('rm_status', '');
            })
            ->update(['rm_status' => 'pending']);

        DB::table('tbl_locator_slips')
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', '')
                    ->orWhere('status', 'On Process');
            })
            ->update(['status' => 'Pending RM Approval']);

        if (Schema::hasTable('tbl_emp_official_info') && Schema::hasTable('tbl_reporting_manager')) {
            $slips = DB::table('tbl_locator_slips')
                ->select(['id', 'hrid'])
                ->whereNull('rm_assignee_hrid')
                ->whereNotNull('hrid')
                ->get();

            foreach ($slips as $slip) {
                $officialInfo = DB::table('tbl_emp_official_info')
                    ->select(['reporting_manager', 'department_id'])
                    ->where('hrid', $slip->hrid)
                    ->first();

                if ($officialInfo === null) {
                    continue;
                }

                $resolvedManagerHrid = null;
                $rawReportingManager = trim((string) ($officialInfo->reporting_manager ?? ''));

                if ($rawReportingManager !== '' && ctype_digit($rawReportingManager)) {
                    $resolvedManagerHrid = (int) $rawReportingManager;
                }

                if ($resolvedManagerHrid === null) {
                    $departmentId = trim((string) ($officialInfo->department_id ?? ''));
                    if ($departmentId !== '' && ctype_digit($departmentId)) {
                        $mappedHrid = DB::table('tbl_reporting_manager')
                            ->whereRaw('CAST(department_id AS UNSIGNED) = ?', [(int) $departmentId])
                            ->value('manager_name');

                        if ($mappedHrid !== null && ctype_digit((string) $mappedHrid)) {
                            $resolvedManagerHrid = (int) $mappedHrid;
                        }
                    }
                }

                if ($resolvedManagerHrid !== null && $resolvedManagerHrid > 0) {
                    DB::table('tbl_locator_slips')
                        ->where('id', $slip->id)
                        ->update(['rm_assignee_hrid' => $resolvedManagerHrid]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_locator_slips', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('tbl_locator_slips', 'rm_remarks') ? 'rm_remarks' : null,
                Schema::hasColumn('tbl_locator_slips', 'rm_action_at') ? 'rm_action_at' : null,
                Schema::hasColumn('tbl_locator_slips', 'rm_acted_by') ? 'rm_acted_by' : null,
                Schema::hasColumn('tbl_locator_slips', 'rm_status') ? 'rm_status' : null,
                Schema::hasColumn('tbl_locator_slips', 'workflow_status') ? 'workflow_status' : null,
                Schema::hasColumn('tbl_locator_slips', 'rm_assignee_hrid') ? 'rm_assignee_hrid' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
