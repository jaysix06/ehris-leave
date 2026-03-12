<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(DatabaseTransactions::class);

it('maps barangay and province names to numeric ids when saving personal contact info', function () {
    if (! Schema::hasTable('tbl_barangay')) {
        Schema::create('tbl_barangay', function ($table): void {
            $table->unsignedInteger('barangay_id')->primary();
            $table->string('barangay_name')->nullable();
        });
    }

    if (! Schema::hasTable('tbl_province')) {
        Schema::create('tbl_province', function ($table): void {
            $table->unsignedInteger('province_id')->primary();
            $table->string('province_name')->nullable();
        });
    }

    if (! Schema::hasTable('tbl_emp_contact_info')) {
        Schema::create('tbl_emp_contact_info', function ($table): void {
            $table->id();
            $table->unsignedInteger('hrid')->nullable();
            $table->string('email')->nullable();
            $table->unsignedInteger('barangay')->nullable();
            $table->unsignedInteger('barangay1')->nullable();
            $table->unsignedInteger('city_municipality')->nullable();
            $table->unsignedInteger('city_municipality1')->nullable();
            $table->unsignedInteger('province')->nullable();
            $table->unsignedInteger('province1')->nullable();
        });
    }

    if (! Schema::hasTable('tbl_municipality')) {
        Schema::create('tbl_municipality', function ($table): void {
            $table->unsignedInteger('municipal_id')->primary();
            $table->unsignedInteger('province_code')->nullable();
            $table->unsignedInteger('municipal_code')->nullable();
            $table->string('municipal_name')->nullable();
        });
    }

    DB::table('tbl_barangay')->where('barangay_id', 901)->delete();
    DB::table('tbl_province')->where('province_id', 99)->delete();
    DB::table('tbl_municipality')->where('municipal_id', 9001)->delete();
    DB::table('tbl_municipality')->where('municipal_name', 'Ozamiz City')->delete();
    DB::table('tbl_barangay')->insert([
        'barangay_id' => 901,
        'barangay_name' => 'Aguada',
    ]);
    DB::table('tbl_province')->insert([
        'province_id' => 99,
        'province_name' => 'Misamis Occidental',
    ]);
    DB::table('tbl_municipality')->insert([
        'municipal_id' => 9001,
        'province_code' => 99,
        'municipal_code' => 104210000,
        'municipal_name' => 'Ozamiz City',
    ]);

    $user = User::factory()->create([
        'active' => true,
        'hrId' => 20856,
        'email' => 'gavino.tan@deped.gov.ph',
    ]);

    $response = $this->actingAs($user)
        ->from('/my-details')
        ->post(route('my-details.personal.store'), [
            'barangay' => 'Aguada',
            'barangay1' => 'Aguada',
            'city_municipality' => 'Ozamiz City',
            'city_municipality1' => 'Ozamiz City',
            'province' => 'Misamis Occidental',
            'province1' => 'Misamis Occidental',
        ]);

    $response->assertRedirect(route('my-details'));
    $response->assertSessionHasNoErrors();

    $saved = DB::table('tbl_emp_contact_info')
        ->where('hrid', 20856)
        ->select(['barangay', 'barangay1', 'city_municipality', 'city_municipality1', 'province', 'province1'])
        ->first();

    expect($saved)->not->toBeNull()
        ->and((int) $saved->barangay)->toBe(901)
        ->and((int) $saved->barangay1)->toBe(901)
        ->and(is_numeric((string) $saved->city_municipality))->toBeTrue()
        ->and(is_numeric((string) $saved->city_municipality1))->toBeTrue()
        ->and(is_numeric((string) $saved->province))->toBeTrue()
        ->and(is_numeric((string) $saved->province1))->toBeTrue();
});
