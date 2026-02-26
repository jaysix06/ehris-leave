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

        // tbl_request_leave is legacy and near row-size limit; store new policy
        // metadata in a companion table instead of altering the wide base table.
        if (! Schema::hasTable('tbl_request_leave_meta')) {
            Schema::create('tbl_request_leave_meta', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('leaved_id')->index();
                $table->text('leave_status')->nullable();
                $table->longText('attachment_meta')->nullable();
                $table->longText('policy_meta')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_request_leave_meta');
    }
};
