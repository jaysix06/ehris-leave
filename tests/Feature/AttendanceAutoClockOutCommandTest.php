<?php

use App\Models\Attendance;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

uses(DatabaseTransactions::class);

it('automatically clocks out all open attendance records', function () {
    if (! Schema::hasTable('tbl_attendance')) {
        Schema::create('tbl_attendance', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('hrid')->nullable();
            $table->dateTime('time_in')->nullable();
            $table->string('time_in_remarks')->nullable();
            $table->dateTime('time_out')->nullable();
            $table->string('time_out_remarks')->nullable();
        });
    }

    Attendance::query()->create([
        'hrid' => 10001,
        'time_in' => now()->subHours(8),
        'time_out' => null,
    ]);

    Attendance::query()->create([
        'hrid' => 10002,
        'time_in' => now()->subHours(7),
        'time_out' => null,
    ]);

    Attendance::query()->create([
        'hrid' => 10003,
        'time_in' => now()->subHours(9),
        'time_out' => now()->subHours(1),
    ]);

    Artisan::call('attendance:auto-clock-out');

    expect(
        Attendance::query()
            ->whereIn('hrid', [10001, 10002, 10003])
            ->whereNull('time_out')
            ->count()
    )->toBe(0)->and(
        Attendance::query()
            ->whereIn('hrid', [10001, 10002, 10003])
            ->whereNotNull('time_out')
            ->count()
    )->toBe(3);
});
