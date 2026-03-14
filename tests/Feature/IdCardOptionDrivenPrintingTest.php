<?php

use App\Http\Controllers\EmployeeManagement\IdCardPrintingController;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

uses(DatabaseTransactions::class);

function ensureRequestedIdTableWithCardOption(): void
{
    if (! Schema::hasTable('tbl_requested_id')) {
        Schema::create('tbl_requested_id', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('hrid')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('fullname')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('status', 64)->default('On Process')->index();
            $table->string('card_option', 32)->nullable()->index();
            $table->timestamps();
        });

        return;
    }

    if (! Schema::hasColumn('tbl_requested_id', 'card_option')) {
        Schema::table('tbl_requested_id', function ($table): void {
            $table->string('card_option', 32)->nullable()->index();
        });
    }
}

function createSolidPng(string $path, int $width, int $height, int $r, int $g, int $b): void
{
    $img = imagecreatetruecolor($width, $height);
    $color = imagecolorallocate($img, $r, $g, $b);
    imagefilledrectangle($img, 0, 0, $width, $height, $color);
    imagepng($img, $path);
    imagedestroy($img);
}

function ensureSampleIdCardTemplates(): string
{
    $dir = storage_path('framework/testing/id-card-templates');
    if (! File::isDirectory($dir)) {
        File::makeDirectory($dir, 0755, true, true);
    }

    $eodbTemplatePath = $dir.DIRECTORY_SEPARATOR.'EODBBB.png';
    if (! is_file($eodbTemplatePath)) {
        createSolidPng($eodbTemplatePath, 2000, 1200, 180, 20, 20);
    }

    $pocketTemplatePath = $dir.DIRECTORY_SEPARATOR.'POCKET.png';
    if (! is_file($pocketTemplatePath)) {
        createSolidPng($pocketTemplatePath, 1000, 1200, 245, 245, 245);
    }

    config()->set('id-card.templates_path', $dir);
    config()->set('id-card.eodb_id_bb_template', 'EODBBB.png');

    return $dir;
}

it('stores selected card option when submitting ID request', function () {
    ensureRequestedIdTableWithCardOption();

    /** @var User $user */
    $user = User::factory()->create([
        'active' => true,
        'hrId' => 88001,
        'email' => 'id-option-user@example.com',
    ]);

    DB::table('tbl_requested_id')->where('user_id', $user->userId)->delete();

    /** @var TestCase $this */
    $response = $this->actingAs($user)
        ->from('/self-service/id-card')
        ->put(route('self-service.id-card.update'), [
            'card_option' => 'pocket_id',
            'id_photo' => UploadedFile::fake()->image('id-photo.jpg', 600, 600),
            'signature' => UploadedFile::fake()->image('signature.png', 400, 160),
            'emergency_contact' => '09179876543',
            'station_no' => 'ST-01',
            'tin' => '123-456-789-000',
            'gsis' => 'GSIS-1234567890',
            'pag_ibig' => 'PAG-1234567890',
            'philhealth' => 'PH-1234567890',
            'birth_date' => '1993-05-20',
            'blood_type' => 'O+',
        ]);

    $response->assertRedirect(route('self-service.id-card'));
    $response->assertSessionHasNoErrors();

    $requestRow = DB::table('tbl_requested_id')
        ->where(function ($query) use ($user): void {
            $query->where('user_id', $user->userId)
                ->orWhere('email', $user->email);
        })
        ->orderByDesc('id')
        ->first();
    expect($requestRow)->not->toBeNull();
    expect($requestRow->card_option ?? null)->toBe('pocket_id');
    expect($requestRow->status ?? null)->toBe('On Process');
});

it('requires id photo and signature when submitting ID request', function () {
    ensureRequestedIdTableWithCardOption();

    /** @var User $user */
    $user = User::factory()->create([
        'active' => true,
        'hrId' => 88002,
        'email' => 'id-option-required@example.com',
    ]);

    /** @var TestCase $this */
    $response = $this->actingAs($user)
        ->from('/self-service/id-card')
        ->put(route('self-service.id-card.update'), [
            'card_option' => 'eodb_id_bb',
        ]);

    $response->assertRedirect('/self-service/id-card');
    $response->assertSessionHasErrors(['id_photo', 'signature']);
});

it('requires pocket id details when pocket id option is selected', function () {
    ensureRequestedIdTableWithCardOption();

    /** @var User $user */
    $user = User::factory()->create([
        'active' => true,
        'hrId' => 88022,
        'email' => 'id-option-pocket-required@example.com',
    ]);

    /** @var TestCase $this */
    $response = $this->actingAs($user)
        ->from('/self-service/id-card')
        ->put(route('self-service.id-card.update'), [
            'card_option' => 'pocket_id',
            'id_photo' => UploadedFile::fake()->image('id-photo.jpg', 600, 600),
            'signature' => UploadedFile::fake()->image('signature.png', 400, 160),
        ]);

    $response->assertRedirect('/self-service/id-card');
    $response->assertSessionHasErrors([
        'emergency_contact',
        'station_no',
        'tin',
        'gsis',
        'pag_ibig',
        'philhealth',
        'birth_date',
        'blood_type',
    ]);
});

it('resolves print context with selected card option', function () {
    ensureRequestedIdTableWithCardOption();

    $requestId = DB::table('tbl_requested_id')->insertGetId([
        'hrid' => 88011,
        'user_id' => null,
        'fullname' => 'Sample Employee',
        'email' => 'sample-employee@example.com',
        'status' => 'On Process',
        'card_option' => 'pocket_id',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $controller = app(IdCardPrintingController::class);
    $method = new ReflectionMethod($controller, 'resolvePrintContext');
    $method->setAccessible(true);

    $context = $method->invoke($controller, $requestId);

    expect($context)->toBeArray();
    expect($context['card_option'] ?? null)->toBe('pocket_id');
});

it('falls back to eodb id bb when card option is invalid', function () {
    ensureRequestedIdTableWithCardOption();

    $requestId = DB::table('tbl_requested_id')->insertGetId([
        'hrid' => 88012,
        'user_id' => null,
        'fullname' => 'Legacy Employee',
        'email' => 'legacy-employee@example.com',
        'status' => 'On Process',
        'card_option' => 'legacy_value',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $controller = app(IdCardPrintingController::class);
    $method = new ReflectionMethod($controller, 'resolvePrintContext');
    $method->setAccessible(true);

    $context = $method->invoke($controller, $requestId);

    expect($context)->toBeArray();
    expect($context['card_option'] ?? null)->toBe('eodb_id_bb');
});

it('includes generated card sample image URLs in self service options', function () {
    /** @var User $user */
    $user = User::factory()->create([
        'active' => true,
        'email' => 'sample-url-user@example.com',
    ]);

    /** @var TestCase $this */
    $this->actingAs($user)
        ->get(route('self-service.id-card'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('SelfService/IdCard')
            ->has('cardOptions', 2)
            ->where('cardOptions.0.sampleImage', route('self-service.id-card.sample', ['option' => 'pocket_id']))
            ->where('cardOptions.1.sampleImage', route('self-service.id-card.sample', ['option' => 'eodb_id_bb']))
        );
});

it('renders ID card sample images for both card options', function (string $option) {
    if (! function_exists('imagecreatetruecolor') || ! function_exists('imagepng')) {
        /** @var TestCase $this */
        $this->markTestSkipped('GD extension is required for ID card sample image rendering tests.');
    }

    ensureSampleIdCardTemplates();

    /** @var User $user */
    $user = User::factory()->create([
        'active' => true,
        'email' => 'sample-render-user-'.$option.'@example.com',
    ]);

    /** @var TestCase $this */
    $response = $this->actingAs($user)
        ->get(route('self-service.id-card.sample', ['option' => $option]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'image/png');
    expect(strlen((string) $response->getContent()))->toBeGreaterThan(100);
})->with(['pocket_id', 'eodb_id_bb']);

it('allows opening self service id card even without official info', function () {
    /** @var User $user */
    $user = User::factory()->create([
        'active' => true,
        'hrId' => 88004,
        'email' => 'id-option-no-official-info@example.com',
    ]);

    /** @var TestCase $this */
    $this->actingAs($user)
        ->get(route('self-service.id-card'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('SelfService/IdCard')
        );
});
