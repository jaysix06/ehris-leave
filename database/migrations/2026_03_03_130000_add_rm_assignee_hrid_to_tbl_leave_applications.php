<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tbl_leave_applications')) {
            return;
        }

        Schema::table('tbl_leave_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('tbl_leave_applications', 'rm_assignee_hrid')) {
                $table->unsignedBigInteger('rm_assignee_hrid')->nullable()->after('employee_id');
                $table->index(['rm_assignee_hrid', 'workflow_status'], 'idx_leave_app_rm_workflow');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tbl_leave_applications')) {
            return;
        }

        Schema::table('tbl_leave_applications', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_leave_applications', 'rm_assignee_hrid')) {
                $table->dropIndex('idx_leave_app_rm_workflow');
                $table->dropColumn('rm_assignee_hrid');
            }
        });
    }
};
