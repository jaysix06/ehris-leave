<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('tbl_emp_personal_info')) {
            return;
        }

        Schema::table('tbl_emp_personal_info', function (Blueprint $table) {
            if (! Schema::hasColumn('tbl_emp_personal_info', 'umid')) {
                $table->string('umid', 64)->nullable()->after('country');
            }

            if (! Schema::hasColumn('tbl_emp_personal_info', 'philsys')) {
                $table->string('philsys', 64)->nullable()->after('philhealth');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('tbl_emp_personal_info')) {
            return;
        }

        Schema::table('tbl_emp_personal_info', function (Blueprint $table) {
            $dropColumns = [];

            if (Schema::hasColumn('tbl_emp_personal_info', 'umid')) {
                $dropColumns[] = 'umid';
            }

            if (Schema::hasColumn('tbl_emp_personal_info', 'philsys')) {
                $dropColumns[] = 'philsys';
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
