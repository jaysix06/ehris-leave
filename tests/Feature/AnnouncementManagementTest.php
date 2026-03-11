<?php

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia;

uses(DatabaseTransactions::class);

beforeEach(function (): void {
    if (! Schema::hasTable('announcements')) {
        Schema::create('announcements', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 255);
            $table->text('content')->nullable();
            $table->json('links')->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->timestamps();
        });
    }
});

it('shows only active announcements on the welcome page', function () {
    Announcement::query()->create([
        'title' => 'Active advisory',
        'content' => 'Visible announcement content',
        'links' => [['label' => 'Memo', 'url' => 'https://example.com/memo']],
        'status' => 'Active',
    ]);

    Announcement::query()->create([
        'title' => 'Inactive advisory',
        'content' => 'Hidden announcement content',
        'status' => 'Inactive',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Welcome')
            ->has('announcements')
            ->where('announcements', function ($announcements): bool {
                $titles = collect($announcements)->pluck('title');

                return $titles->contains('Active advisory') && ! $titles->contains('Inactive advisory');
            })
        );
});

it('requires authentication to access announcement management', function () {
    $this->get('/utilities/announcement-management')->assertRedirect('/login');
});

it('stores an announcement with links from utilities management', function () {
    $user = User::factory()->create(['active' => true]);

    $this->actingAs($user)
        ->post('/utilities/announcement-management', [
            'title' => 'New advisory',
            'content' => 'Please see the references below.',
            'status' => 'Active',
            'links' => [
                ['label' => 'Guidelines', 'url' => 'https://example.com/guidelines'],
                ['label' => 'Memo', 'url' => 'https://example.com/memo'],
            ],
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('announcements', [
        'title' => 'New advisory',
        'status' => 'Active',
    ]);

    $announcement = Announcement::query()->where('title', 'New advisory')->firstOrFail();

    expect($announcement->links)->toBeArray()
        ->and($announcement->links)->toHaveCount(2)
        ->and($announcement->links[0]['url'])->toBe('https://example.com/guidelines');
});
