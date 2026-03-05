<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\getJson;

beforeEach(function () {
    if (! Schema::hasTable('tbl_user')) {
        Schema::create('tbl_user', function (Blueprint $table): void {
            $table->increments('userId');
            $table->unsignedInteger('hrId')->nullable();
            $table->string('email')->nullable();
            $table->string('lastname')->nullable();
            $table->string('firstname')->nullable();
            $table->string('middlename')->nullable();
            $table->string('extname')->nullable();
            $table->string('avatar')->nullable();
            $table->string('job_title')->nullable();
            $table->string('role')->nullable();
            $table->string('fullname')->nullable();
        });
    }

});

test('employee details api rejects missing api key', function () {
    config(['api_access.key' => 'my-local-key']);

    getJson('/api/employee-details/1001001')
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Unauthorized.');
});

test('employee details api rejects invalid api key', function () {
    config(['api_access.key' => 'my-local-key']);

    getJson('/api/employee-details/1001001', [
        'X-API-KEY' => 'wrong-key',
    ])->assertUnauthorized()
        ->assertJsonPath('message', 'Unauthorized.');
});

test('employee details api returns data with valid api key', function () {
    config(['api_access.key' => 'my-local-key']);

    DB::table('tbl_user')->insert([
        'hrId' => 1001001,
        'email' => 'tests@gmail.com',
        'lastname' => 'DELA CRUZ',
        'firstname' => 'JUAN',
        'middlename' => 'SANTOS',
        'extname' => 'JR.',
        'avatar' => 'avatar-default.jpg',
        'job_title' => 'Teacher I',
        'role' => 'employee',
        'fullname' => 'JUAN SANTOS DELA CRUZ JR.',
    ]);

    getJson('/api/employee-details/1001001', [
        'X-API-KEY' => 'my-local-key',
    ])->assertOk()
        ->assertJsonPath('data.userDetails.email', 'tests@gmail.com')
        ->assertJsonMissingPath('data.officialInfo');
});
