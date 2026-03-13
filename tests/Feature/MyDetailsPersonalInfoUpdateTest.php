<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(DatabaseTransactions::class);

it('accepts numeric height and weight when updating personal info', function () {
    $user = User::factory()->create([
        'active' => true,
        'hrId' => 19132,
    ]);

    $response = $this->actingAs($user)
        ->from('/my-details')
        ->post(route('my-details.personal.store'), [
            'height' => 1.75,
            'weight' => 68.5,
        ]);

    $response->assertRedirect(route('my-details'));
    $response->assertSessionHasNoErrors();
});

it('saves umid and philsys in personal info', function () {
    if (! Schema::hasTable('tbl_emp_personal_info')) {
        $this->markTestSkipped('tbl_emp_personal_info does not exist in this environment.');
    }

    if (! Schema::hasColumn('tbl_emp_personal_info', 'umid') || ! Schema::hasColumn('tbl_emp_personal_info', 'philsys')) {
        $this->markTestSkipped('tbl_emp_personal_info.umid/philsys columns are missing. Run migrations first.');
    }

    $user = User::factory()->create([
        'active' => true,
        'hrId' => 19133,
    ]);

    $response = $this->actingAs($user)
        ->from('/my-details')
        ->post(route('my-details.personal.store'), [
            'umid' => '1234-5678-9012-3456',
            'philsys' => '1234-5678-9012',
        ]);

    $response->assertRedirect(route('my-details'));
    $response->assertSessionHasNoErrors();

    $saved = DB::table('tbl_emp_personal_info')
        ->where('hrid', 19133)
        ->select(['umid', 'philsys'])
        ->first();

    expect($saved)->not->toBeNull()
        ->and((string) $saved->umid)->toBe('1234-5678-9012-3456')
        ->and((string) $saved->philsys)->toBe('1234-5678-9012');
});
