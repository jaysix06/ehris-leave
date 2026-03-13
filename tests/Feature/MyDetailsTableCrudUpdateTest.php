<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(DatabaseTransactions::class);

function ensureMyDetailsTable(string $table, callable $creator): void
{
    if (! Schema::hasTable($table)) {
        Schema::create($table, $creator);
    }
}

it('updates eligibility rows via my details endpoint', function () {
    ensureMyDetailsTable('tbl_emp_civil_service_info', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('hrid')->nullable();
        $table->string('title')->nullable();
        $table->string('rating')->nullable();
        $table->string('date_exam')->nullable();
        $table->string('place_exam')->nullable();
        $table->string('license_no')->nullable();
    });

    /** @var User $user */
    $user = User::factory()->create([
        'active' => true,
        'hrId' => 32001,
    ]);

    DB::table('tbl_emp_civil_service_info')->where('hrid', 32001)->delete();

    /** @var TestCase $this */
    $response = $this->actingAs($user)
        ->from('/my-details')
        ->post(route('my-details.eligibility.store'), [
            'eligibility' => [
                [
                    'title' => 'Career Service Professional',
                    'rating' => '85.50',
                    'date_exam' => '2018-04-15',
                    'place_exam' => 'Manila',
                    'license_no' => 'CS-001',
                ],
            ],
        ]);

    $response->assertRedirect(route('my-details'));
    $response->assertSessionHasNoErrors();
    expect(DB::table('tbl_emp_civil_service_info')->where('hrid', 32001)->count())->toBe(1);
});

it('updates work experience rows via my details endpoint', function () {
    ensureMyDetailsTable('tbl_emp_work_experience_info', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('hrid')->nullable();
        $table->string('company_name')->nullable();
        $table->string('position_title')->nullable();
        $table->string('inclusive_date_from')->nullable();
        $table->string('inclusive_date_to')->nullable();
        $table->string('employment_status')->nullable();
    });

    /** @var User $user */
    $user = User::factory()->create([
        'active' => true,
        'hrId' => 32002,
    ]);

    DB::table('tbl_emp_work_experience_info')->where('hrid', 32002)->delete();

    /** @var TestCase $this */
    $response = $this->actingAs($user)
        ->from('/my-details')
        ->post(route('my-details.work-experience.store'), [
            'workExperience' => [
                [
                    'company_name' => 'DepEd Ozamiz',
                    'position_title' => 'Teacher I',
                    'inclusive_date_from' => '2020-01-01',
                    'inclusive_date_to' => '2022-12-31',
                    'employment_status' => 'Permanent',
                ],
            ],
        ]);

    $response->assertRedirect(route('my-details'));
    $response->assertSessionHasNoErrors();
    expect(DB::table('tbl_emp_work_experience_info')->where('hrid', 32002)->count())->toBe(1);
});

it('updates voluntary work rows via my details endpoint', function () {
    ensureMyDetailsTable('tbl_emp_voluntary_work', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('hrid')->nullable();
        $table->string('name_address_org')->nullable();
        $table->string('inclusive_date_from')->nullable();
        $table->string('inclusive_date_to')->nullable();
        $table->string('number_hours')->nullable();
        $table->string('position_nature_of_work')->nullable();
    });

    /** @var User $user */
    $user = User::factory()->create([
        'active' => true,
        'hrId' => 32003,
    ]);

    DB::table('tbl_emp_voluntary_work')->where('hrid', 32003)->delete();

    /** @var TestCase $this */
    $response = $this->actingAs($user)
        ->from('/my-details')
        ->post(route('my-details.voluntary-work.store'), [
            'voluntaryWork' => [
                [
                    'name_address_org' => 'Red Cross Chapter',
                    'inclusive_date_from' => '2023-01-01',
                    'inclusive_date_to' => '2023-06-30',
                    'number_hours' => '48',
                    'position_nature_of_work' => 'Volunteer Trainer',
                ],
            ],
        ]);

    $response->assertRedirect(route('my-details'));
    $response->assertSessionHasNoErrors();
    expect(DB::table('tbl_emp_voluntary_work')->where('hrid', 32003)->count())->toBe(1);
});

it('updates training rows via my details endpoint', function () {
    ensureMyDetailsTable('tbl_emp_training', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('hrid')->nullable();
        $table->string('training_title')->nullable();
        $table->string('training_venue')->nullable();
        $table->string('start_date')->nullable();
        $table->string('end_date')->nullable();
        $table->string('number_hours')->nullable();
    });

    /** @var User $user */
    $user = User::factory()->create([
        'active' => true,
        'hrId' => 32004,
    ]);

    DB::table('tbl_emp_training')->where('hrid', 32004)->delete();

    /** @var TestCase $this */
    $response = $this->actingAs($user)
        ->from('/my-details')
        ->post(route('my-details.training.store'), [
            'training' => [
                [
                    'training_title' => 'Classroom Management',
                    'training_venue' => 'Ozamiz City',
                    'start_date' => '2024-03-10',
                    'end_date' => '2024-03-12',
                    'number_hours' => '24',
                ],
            ],
        ]);

    $response->assertRedirect(route('my-details'));
    $response->assertSessionHasNoErrors();
    expect(DB::table('tbl_emp_training')->where('hrid', 32004)->count())->toBe(1);
});

it('updates awards rows via my details endpoint', function () {
    ensureMyDetailsTable('tbl_awards', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('hrid')->nullable();
        $table->string('award_title')->nullable();
        $table->string('category')->nullable();
        $table->string('school_year')->nullable();
        $table->string('award')->nullable();
    });

    /** @var User $user */
    $user = User::factory()->create([
        'active' => true,
        'hrId' => 32005,
    ]);

    DB::table('tbl_awards')->where('hrid', 32005)->delete();

    /** @var TestCase $this */
    $response = $this->actingAs($user)
        ->from('/my-details')
        ->post(route('my-details.awards.store'), [
            'awards' => [
                [
                    'award_title' => 'Outstanding Teacher',
                    'category' => 'Division',
                    'school_year' => '2025-2026',
                    'award' => 'Gold',
                ],
            ],
        ]);

    $response->assertRedirect(route('my-details'));
    $response->assertSessionHasNoErrors();
    expect(DB::table('tbl_awards')->where('hrid', 32005)->count())->toBe(1);
});
