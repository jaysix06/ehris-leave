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
        Schema::create('tbl_user', function (Blueprint $table) {
            $table->integer('userId')->primary();
            $table->integer('hrId')->nullable();
            $table->string('email', 255)->nullable();
            $table->string('password', 255)->nullable();
            $table->string('lastname', 255)->nullable();
            $table->string('firstname', 255)->nullable();
            $table->string('middlename', 255)->nullable();
            $table->string('extname', 50)->nullable();
            $table->string('avatar', 255)->default('avatar-default.jpg');
            $table->string('job_title', 255)->nullable();
            $table->string('role', 255)->nullable();
            $table->boolean('active')->default(true);
            $table->date('date_created')->nullable();
            $table->string('fullname', 255)->nullable();
            $table->integer('department_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_user');
    }
};
