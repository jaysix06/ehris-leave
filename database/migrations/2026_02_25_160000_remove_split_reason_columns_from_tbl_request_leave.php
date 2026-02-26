<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tbl_request_leave')) {
            return;
        }

        Schema::table('tbl_request_leave', function (Blueprint $table) {
            $columnsToDrop = [];

            foreach ([
                'case_in_vacation',
                'case_vacation_specify',
                'case_sick_leave',
                'case_sick_specify',
            ] as $column) {
                if (Schema::hasColumn('tbl_request_leave', $column)) {
                    $columnsToDrop[] = $column;
                }
            }

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tbl_request_leave')) {
            return;
        }

        Schema::table('tbl_request_leave', function (Blueprint $table) {
            if (! Schema::hasColumn('tbl_request_leave', 'case_in_vacation')) {
                $table->string('case_in_vacation', 255)->nullable()->after('monthly_salary');
            }

            if (! Schema::hasColumn('tbl_request_leave', 'case_vacation_specify')) {
                $table->string('case_vacation_specify', 255)->nullable()->after('case_in_vacation');
            }

            if (! Schema::hasColumn('tbl_request_leave', 'case_sick_leave')) {
                $table->string('case_sick_leave', 255)->nullable()->after('case_vacation_specify');
            }

            if (! Schema::hasColumn('tbl_request_leave', 'case_sick_specify')) {
                $table->string('case_sick_specify', 255)->nullable()->after('case_sick_leave');
            }
        });
    }
};

