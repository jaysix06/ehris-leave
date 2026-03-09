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
        Schema::table('upcoming_events', function (Blueprint $table) {
            $table->string('color', 32)->nullable()->default('blue')->after('description');
        });

        DB::table('upcoming_events')->whereNull('color')->update(['color' => 'blue']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('upcoming_events', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};
