<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_leave_applications', function (Blueprint $table) {
            $table->bigIncrements('leave_application_id');
            $table->unsignedBigInteger('employee_hrid')->nullable();
            $table->string('employee_id', 50)->nullable();
            $table->unsignedBigInteger('rm_assignee_hrid')->nullable();

            $table->string('leave_type', 255);
            $table->string('leave_for', 100);
            $table->date('leave_start_date');
            $table->date('leave_end_date');
            $table->unsignedInteger('leave_days');

            // within | abroad
            $table->enum('reason_for_leave', ['within', 'abroad'])->nullable();
            $table->text('reason_text')->nullable();

            // requested | not requested
            $table->enum('commutation', ['requested', 'not requested'])->nullable();
            $table->text('supervisor_reviewer_notes')->nullable();
            $table->json('supporting_documents')->nullable();
            $table->dateTime('date_applied');

            // RM -> HR -> SDS workflow
            $table->enum('workflow_status', ['pending_rm', 'pending_hr', 'pending_sds', 'approved', 'disapproved'])
                ->default('pending_rm');

            $table->enum('rm_status', ['pending', 'approved', 'disapproved'])->default('pending');
            $table->dateTime('rm_action_at')->nullable();
            $table->unsignedBigInteger('rm_acted_by')->nullable();
            $table->text('rm_remarks')->nullable();

            $table->enum('hr_status', ['pending', 'approved', 'disapproved'])->default('pending');
            $table->dateTime('hr_action_at')->nullable();
            $table->unsignedBigInteger('hr_acted_by')->nullable();
            $table->text('hr_remarks')->nullable();

            $table->enum('sds_status', ['pending', 'approved', 'disapproved'])->default('pending');
            $table->dateTime('sds_action_at')->nullable();
            $table->unsignedBigInteger('sds_acted_by')->nullable();
            $table->text('sds_remarks')->nullable();

            // retained for existing calamity policy checks
            $table->date('calamity_date')->nullable();

            $table->timestamps();

            $table->index(['employee_hrid', 'date_applied'], 'idx_leave_app_emp_applied');
            $table->index(['rm_assignee_hrid', 'workflow_status'], 'idx_leave_app_rm_workflow');
            $table->index(['workflow_status', 'date_applied'], 'idx_leave_app_workflow_applied');
            $table->index(['leave_type', 'leave_start_date'], 'idx_leave_app_type_start');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_leave_applications');
    }
};
