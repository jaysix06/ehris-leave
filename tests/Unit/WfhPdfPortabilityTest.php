<?php

use App\Http\Controllers\SelfService\WfhTimeInOutController;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

it('builds a file URI that safely encodes unix paths with spaces', function () {
    $controller = new WfhTimeInOutController;
    $method = new ReflectionMethod(WfhTimeInOutController::class, 'toFileUri');
    $method->setAccessible(true);

    $uri = $method->invoke($controller, '/var/www/public/fonts/Bookman Old Style.ttf');

    expect($uri)->toBe('file:///var/www/public/fonts/Bookman%20Old%20Style.ttf');
});

it('builds a file URI that safely encodes windows paths with spaces', function () {
    $controller = new WfhTimeInOutController;
    $method = new ReflectionMethod(WfhTimeInOutController::class, 'toFileUri');
    $method->setAccessible(true);

    $uri = $method->invoke($controller, 'C:\\fonts\\Bookman Old Style.ttf');

    expect($uri)->toBe('file:///C:/fonts/Bookman%20Old%20Style.ttf');
});

it('resolves a configured or fallback WFH PDF font path when a known font exists', function () {
    $controller = new WfhTimeInOutController;
    $method = new ReflectionMethod(WfhTimeInOutController::class, 'resolveWfhPdfNameFontPath');
    $method->setAccessible(true);

    $resolvedPath = $method->invoke($controller);

    expect($resolvedPath)->not->toBeNull()
        ->and(is_readable((string) $resolvedPath))->toBeTrue();
});

it('resolves numeric station id to department name for PDF station column', function () {
    if (! Schema::hasTable('tbl_department')) {
        Schema::create('tbl_department', function ($table): void {
            $table->id();
            $table->unsignedInteger('department_id')->nullable();
            $table->string('department_name')->nullable();
        });
    }

    DB::table('tbl_department')->where('department_id', 113)->delete();
    DB::table('tbl_department')->insert([
        'department_id' => 113,
        'department_name' => 'Sample National High School',
    ]);

    $employee = new Employee;
    $employee->office = '113';

    $controller = new WfhTimeInOutController;
    $method = new ReflectionMethod(WfhTimeInOutController::class, 'resolveEmployeeStationForPdf');
    $method->setAccessible(true);

    $station = $method->invoke($controller, $employee);

    expect($station)->toBe('Sample National High School');
});

it('resolves station from logged in user department id', function () {
    if (! Schema::hasTable('tbl_department')) {
        Schema::create('tbl_department', function ($table): void {
            $table->id();
            $table->unsignedInteger('department_id')->nullable();
            $table->string('department_name')->nullable();
        });
    }

    DB::table('tbl_department')->where('department_id', 113)->delete();
    DB::table('tbl_department')->insert([
        'department_id' => 113,
        'department_name' => 'Sample National High School',
    ]);

    $user = new User;
    $user->department_id = 113;

    $controller = new WfhTimeInOutController;
    $method = new ReflectionMethod(WfhTimeInOutController::class, 'resolveStationFromAuthenticatedUser');
    $method->setAccessible(true);

    $station = $method->invoke($controller, $user);

    expect($station)->toBe('Sample National High School');
});

it('uses tightened target and priority columns with wider station column in WFH PDF table', function () {
    $controller = new WfhTimeInOutController;
    $method = new ReflectionMethod(WfhTimeInOutController::class, 'buildWfhPdfHtml');
    $method->setAccessible(true);

    $html = $method->invoke(
        $controller,
        null,
        null,
        '(March 01, 2026-March 31, 2026)',
        'Jane Doe',
        'Sample Station',
        [
            [
                'targeted_task' => 'Task A',
                'accomplishment' => 'Done',
                'date_range' => '03/01/2026-03/02/2026',
                'priority' => 'High',
            ],
        ]
    );

    expect($html)->toContain('th.col-task, td.col-task { text-align: left; width: 30%; }')
        ->and($html)->toContain('th.col-priority, td.col-priority { text-align: center; width: 9%; }')
        ->and($html)->toContain('th.col-station, td.col-station { text-align: left; width: 15%; }');
});
