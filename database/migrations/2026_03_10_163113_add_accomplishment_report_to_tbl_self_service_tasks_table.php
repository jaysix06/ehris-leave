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
        Schema::table('tbl_self_service_tasks', function (Blueprint $table) {
            $table->text('accomplishment_report')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_self_service_tasks', function (Blueprint $table) {
            $table->dropColumn('accomplishment_report');
        });
    }
};
