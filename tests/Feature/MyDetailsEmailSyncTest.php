<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(DatabaseTransactions::class);

it('syncs the official info deped email to the tbl_user email column', function () {
    if (! Schema::hasTable('tbl_emp_official_info')) {
        Schema::create('tbl_emp_official_info', function ($table): void {
            $table->id();
            $table->unsignedInteger('hrid')->nullable();
            $table->string('email')->nullable();
        });
    }

    $user = User::factory()->create([
        'active' => true,
        'role' => 'HR Manager',
        'hrId' => 30101,
        'email' => 'old.official@deped.gov.ph',
    ]);

    $response = $this->actingAs($user)
        ->from(route('my-details'))
        ->post(route('my-details.official.store'), [
            'email' => 'new.official@deped.gov.ph',
        ]);

    $response->assertRedirect(route('my-details'));
    $response->assertSessionHasNoErrors();

    expect($user->fresh()->email)->toBe('new.official@deped.gov.ph')
        ->and(DB::table('tbl_emp_official_info')->where('hrid', 30101)->value('email'))->toBe('new.official@deped.gov.ph');
});

it('syncs the personal info email to the tbl_user personal_email column', function () {
    if (! Schema::hasTable('tbl_emp_contact_info')) {
        Schema::create('tbl_emp_contact_info', function ($table): void {
            $table->id();
            $table->unsignedInteger('hrid')->nullable();
            $table->string('email')->nullable();
        });
    }

    $user = User::factory()->create([
        'active' => true,
        'hrId' => 30102,
        'email' => 'employee@deped.gov.ph',
        'personal_email' => null,
    ]);

    $response = $this->actingAs($user)
        ->from(route('my-details'))
        ->post(route('my-details.personal.store'), [
            'email' => 'person@example.com',
        ]);

    $response->assertRedirect(route('my-details'));
    $response->assertSessionHasNoErrors();

    expect($user->fresh()->personal_email)->toBe('person@example.com')
        ->and(DB::table('tbl_emp_contact_info')->where('hrid', 30102)->value('email'))->toBe('person@example.com');
});
