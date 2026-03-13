<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia;

uses(DatabaseTransactions::class);

beforeEach(function () {
    if (! Schema::hasTable('tbl_business')) {
        Schema::create('tbl_business', function ($table): void {
            $table->id();
            $table->unsignedInteger('BusinessUnitId')->nullable();
            $table->string('BusinessUnit')->nullable();
        });
    }

    if (! Schema::hasTable('tbl_department')) {
        Schema::create('tbl_department', function ($table): void {
            $table->id();
            $table->unsignedInteger('department_id')->nullable();
            $table->string('department_name')->nullable();
            $table->unsignedInteger('business_id')->nullable();
        });
    }

    DB::table('tbl_business')->updateOrInsert(
        ['BusinessUnitId' => 9001],
        ['BusinessUnit' => 'Test District'],
    );

    DB::table('tbl_department')->updateOrInsert(
        ['department_id' => 9002],
        [
            'department_name' => 'Test School',
            'business_id' => 9001,
        ],
    );
});

it('shows only employee and teacher roles on the registration page', function () {
    $this->get('/register')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('auth/Register')
            ->where('roles', ['Employee', 'Teacher'])
        );
});

it('rejects registration requests with roles outside employee and teacher', function () {
    $this->from('/register')
        ->post('/register', [
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => 'register-role@example.com',
            'role' => 'System Admin',
            'district' => '9001',
            'station' => '9002',
        ])
        ->assertRedirect('/register')
        ->assertSessionHasErrors('role');
});
