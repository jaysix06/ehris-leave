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
        Schema::create('tbl_requested_id', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hrid')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index()->comment('tbl_user.userId');
            $table->string('fullname')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('status', 64)->default('On Process')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_requested_id');
    }
};
