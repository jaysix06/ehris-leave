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
        if (! Schema::hasTable('tbl_user')) {
            return;
        }

        Schema::table('tbl_user', function (Blueprint $table) {
            if (! Schema::hasColumn('tbl_user', 'remember_token')) {
                $table->string('remember_token', 100)->nullable()->after('password');
            }

            if (! Schema::hasColumn('tbl_user', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('remember_token');
            }

            if (! Schema::hasColumn('tbl_user', 'two_factor_secret')) {
                $table->text('two_factor_secret')->nullable()->after('email_verified_at');
            }

            if (! Schema::hasColumn('tbl_user', 'two_factor_recovery_codes')) {
                $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            }

            if (! Schema::hasColumn('tbl_user', 'two_factor_confirmed_at')) {
                $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('tbl_user')) {
            return;
        }

        Schema::table('tbl_user', function (Blueprint $table) {
            $columnsToDrop = [];

            if (Schema::hasColumn('tbl_user', 'remember_token')) {
                $columnsToDrop[] = 'remember_token';
            }

            if (Schema::hasColumn('tbl_user', 'email_verified_at')) {
                $columnsToDrop[] = 'email_verified_at';
            }

            if (Schema::hasColumn('tbl_user', 'two_factor_secret')) {
                $columnsToDrop[] = 'two_factor_secret';
            }

            if (Schema::hasColumn('tbl_user', 'two_factor_recovery_codes')) {
                $columnsToDrop[] = 'two_factor_recovery_codes';
            }

            if (Schema::hasColumn('tbl_user', 'two_factor_confirmed_at')) {
                $columnsToDrop[] = 'two_factor_confirmed_at';
            }

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
