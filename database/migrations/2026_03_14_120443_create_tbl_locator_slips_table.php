<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_locator_slips', function (Blueprint $table) {
            $table->id();
            $table->string('control_no')->unique();
            $table->unsignedBigInteger('hrid')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->date('date_of_filing');
            $table->string('employee_name');
            $table->string('position_designation')->nullable();
            $table->string('permanent_station')->nullable();
            $table->string('purpose_of_travel');
            $table->enum('travel_type', ['official_business', 'official_time']);
            $table->date('travel_date');
            $table->time('time_out')->nullable();
            $table->time('time_in')->nullable();
            $table->string('destination');
            $table->string('status')->default('On Process');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_locator_slips');
    }
};
