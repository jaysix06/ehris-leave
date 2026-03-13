<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia;

uses(DatabaseTransactions::class);

it('does not preload barangay options in the my details page payload', function () {
    $user = User::factory()->create([
        'active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('my-details'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('MyDetails')
            ->where('contactOptions.barangays', [])
            ->where('contactOptions.municipalities', [])
        );
});

it('returns only municipalities for the selected province', function () {
    if (! Schema::hasTable('tbl_municipality')) {
        Schema::create('tbl_municipality', function ($table): void {
            $table->unsignedInteger('municipal_id')->primary();
            $table->unsignedInteger('province_code')->nullable();
            $table->unsignedInteger('municipal_code')->nullable();
            $table->string('municipal_name')->nullable();
        });
    }

    DB::table('tbl_municipality')->whereIn('municipal_id', [801, 802, 803])->delete();
    DB::table('tbl_municipality')->insert([
        [
            'municipal_id' => 801,
            'province_code' => 104200000,
            'municipal_code' => 104210000,
            'municipal_name' => 'Ozamiz City',
        ],
        [
            'municipal_id' => 802,
            'province_code' => 104200000,
            'municipal_code' => 104220000,
            'municipal_name' => 'Tangub City',
        ],
        [
            'municipal_id' => 803,
            'province_code' => 104300000,
            'municipal_code' => 104310000,
            'municipal_name' => 'Oroquieta City',
        ],
    ]);

    $user = User::factory()->create([
        'active' => true,
    ]);

    $this->actingAs($user)
        ->getJson(route('my-details.municipalities', ['province_code' => 104200000]))
        ->assertOk()
        ->assertJsonFragment([
            'municipal_code' => 104210000,
            'name' => 'Ozamiz City',
        ])
        ->assertJsonFragment([
            'municipal_code' => 104220000,
            'name' => 'Tangub City',
        ])
        ->assertJsonMissing([
            'municipal_code' => 104310000,
            'name' => 'Oroquieta City',
        ]);
});

it('returns only barangays for the selected municipality', function () {
    if (! Schema::hasTable('tbl_barangay')) {
        Schema::create('tbl_barangay', function ($table): void {
            $table->unsignedInteger('barangay_id')->primary();
            $table->unsignedInteger('municipal_code')->nullable();
            $table->string('barangay_name')->nullable();
        });
    }

    DB::table('tbl_barangay')->whereIn('barangay_id', [901, 902, 903])->delete();
    DB::table('tbl_barangay')->insert([
        [
            'barangay_id' => 901,
            'municipal_code' => 104210000,
            'barangay_name' => 'Aguada',
        ],
        [
            'barangay_id' => 902,
            'municipal_code' => 104210000,
            'barangay_name' => 'Bacolod',
        ],
        [
            'barangay_id' => 903,
            'municipal_code' => 104220000,
            'barangay_name' => 'Poblacion',
        ],
    ]);

    $user = User::factory()->create([
        'active' => true,
    ]);

    $this->actingAs($user)
        ->getJson(route('my-details.barangays', ['municipal_code' => 104210000]))
        ->assertOk()
        ->assertJsonFragment([
            'id' => 901,
            'name' => 'Aguada',
        ])
        ->assertJsonFragment([
            'id' => 902,
            'name' => 'Bacolod',
        ])
        ->assertJsonMissing([
            'id' => 903,
            'name' => 'Poblacion',
        ]);
});

it('maps barangay and province names to numeric ids when saving personal contact info', function () {
    if (! Schema::hasTable('tbl_barangay')) {
        Schema::create('tbl_barangay', function ($table): void {
            $table->unsignedInteger('barangay_id')->primary();
            $table->unsignedInteger('municipal_code')->nullable();
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
        'municipal_code' => 104210000,
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

it('resolves stored contact lookup codes to display names on my details', function () {
    if (! Schema::hasTable('tbl_barangay')) {
        Schema::create('tbl_barangay', function ($table): void {
            $table->unsignedInteger('barangay_id')->primary();
            $table->unsignedInteger('barangay_code')->nullable();
            $table->unsignedInteger('municipal_code')->nullable();
            $table->string('barangay_name')->nullable();
        });
    }

    if (! Schema::hasTable('tbl_province')) {
        Schema::create('tbl_province', function ($table): void {
            $table->unsignedInteger('province_id')->primary();
            $table->unsignedInteger('province_code')->nullable();
            $table->string('province_name')->nullable();
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

    if (! Schema::hasTable('tbl_emp_contact_info')) {
        Schema::create('tbl_emp_contact_info', function ($table): void {
            $table->id();
            $table->unsignedInteger('hrid')->nullable();
            $table->unsignedInteger('barangay')->nullable();
            $table->unsignedInteger('barangay1')->nullable();
            $table->unsignedInteger('city_municipality')->nullable();
            $table->unsignedInteger('city_municipality1')->nullable();
            $table->unsignedInteger('province')->nullable();
            $table->unsignedInteger('province1')->nullable();
            $table->string('email')->nullable();
        });
    }

    DB::table('tbl_barangay')->whereIn('barangay_id', [951, 952])->delete();
    DB::table('tbl_municipality')->where('municipal_id', 9501)->delete();
    DB::table('tbl_province')->where('province_id', 95)->delete();

    DB::table('tbl_barangay')->insert([
        'barangay_id' => 951,
        'barangay_code' => 104210123,
        'municipal_code' => 104210000,
        'barangay_name' => 'Aguada',
    ]);

    DB::table('tbl_municipality')->insert([
        'municipal_id' => 9501,
        'province_code' => 104200000,
        'municipal_code' => 104210000,
        'municipal_name' => 'Ozamiz City',
    ]);

    DB::table('tbl_province')->insert([
        'province_id' => 95,
        'province_code' => 104200000,
        'province_name' => 'Misamis Occidental',
    ]);

    $user = User::factory()->create([
        'active' => true,
        'hrId' => 20857,
    ]);

    DB::table('tbl_emp_contact_info')->where('hrid', 20857)->delete();
    DB::table('tbl_emp_contact_info')->insert([
        'hrid' => 20857,
        'barangay' => 104210123,
        'barangay1' => 104210123,
        'city_municipality' => 104210000,
        'city_municipality1' => 104210000,
        'province' => 104200000,
        'province1' => 104200000,
    ]);

    $this->actingAs($user)
        ->get(route('my-details'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('MyDetails')
            ->where('contactInfo.residential_barangay_name', 'Aguada')
            ->where('contactInfo.permanent_barangay_name', 'Aguada')
            ->where('contactInfo.residential_city_name', fn ($value) => strcasecmp((string) $value, 'Ozamiz City') === 0)
            ->where('contactInfo.permanent_city_name', fn ($value) => strcasecmp((string) $value, 'Ozamiz City') === 0)
            ->where('contactInfo.residential_province_name', fn ($value) => strcasecmp((string) $value, 'Misamis Occidental') === 0)
            ->where('contactInfo.permanent_province_name', fn ($value) => strcasecmp((string) $value, 'Misamis Occidental') === 0)
        );
});
