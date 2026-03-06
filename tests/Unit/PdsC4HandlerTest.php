<?php

use App\Http\Controllers\MyDetails\PdsC4Handler;

it('defaults C4 answers to NO when fields are empty', function () {
    $handler = new PdsC4Handler;

    $states = $handler->buildControlCheckStates((object) []);

    expect($states['Check Box 1'])->toBeFalse()
        ->and($states['Check Box 2'])->toBeTrue()
        ->and($states['Check Box 3'])->toBeFalse()
        ->and($states['Check Box 4'])->toBeTrue()
        ->and($states['Check Box 7'])->toBeFalse()
        ->and($states['Check Box 8'])->toBeTrue()
        ->and($states['Check Box 13'])->toBeFalse()
        ->and($states['Check Box 14'])->toBeTrue()
        ->and($states['Check Box 26'])->toBeFalse()
        ->and($states['Check Box 27'])->toBeTrue()
        ->and($states['Check Box 28'])->toBeFalse()
        ->and($states['Check Box 29'])->toBeTrue();
});

it('checks YES and maps detail cells when C4 data exists', function () {
    $handler = new PdsC4Handler;
    $personalInfo = (object) [
        'q35b' => 'yes',
        'q35b_details' => 'Criminal case filed',
        'q35b_date_filed' => '2026-03-01',
        'q35b_status' => 'Pending',
        'q40c' => 1,
        'solo_parent_id_no' => 'SP-1001',
    ];

    $cellMap = $handler->buildCellMap(
        $personalInfo,
        fn ($value) => '01/03/2026',
        fn ($value) => (string) $value
    );
    $states = $handler->buildControlCheckStates($personalInfo);

    expect($states['Check Box 7'])->toBeTrue()
        ->and($states['Check Box 8'])->toBeFalse()
        ->and($states['Check Box 17'])->toBeTrue()
        ->and($states['Check Box 20'])->toBeFalse()
        ->and($cellMap['G19'])->toBe('Criminal case filed')
        ->and($cellMap['H20'])->toBe('01/03/2026')
        ->and($cellMap['G21'])->toBe('Pending')
        ->and($cellMap['G48'])->toBe('SP-1001');
});
