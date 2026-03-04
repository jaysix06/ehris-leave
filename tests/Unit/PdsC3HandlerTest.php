<?php

use App\Http\Controllers\MyDetails\PdsC3Handler;

it('builds C3 map for voluntary work learning and other information', function () {
    $handler = new PdsC3Handler;

    $voluntaryWork = [
        (object) [
            'organization' => 'Red Cross Chapter',
            'start_date' => '2024-01-01',
            'end_date' => '2024-03-01',
            'number_hours' => '120',
            'position' => 'Volunteer Nurse',
        ],
    ];
    $learningAndDevelopment = [
        (object) [
            'training_title' => 'Disaster Risk Reduction Training',
            'start_date' => '2024-04-10',
            'end_date' => '2024-04-12',
            'number_hours' => '24',
            'type_of_ld' => 'Technical',
            'conducted_sponsored_by' => 'DepEd Region X',
        ],
    ];
    $expertise = [(object) ['expertise' => 'Public Speaking']];
    $awards = [(object) ['award_title' => 'Outstanding Volunteer 2024']];
    $affiliation = [(object) ['affiliation' => 'Philippine Red Cross']];

    $formatDate = fn ($value) => $value ? "date:{$value}" : null;
    $pdsValue = fn ($value) => ($value === null || trim((string) $value) === '') ? 'N/A' : (string) $value;

    $cellMap = $handler->buildCellMap(
        $voluntaryWork,
        $learningAndDevelopment,
        $expertise,
        $awards,
        $affiliation,
        $formatDate,
        $pdsValue
    );

    expect($cellMap['A6'])->toBe('Red Cross Chapter')
        ->and($cellMap['E6'])->toBe('date:2024-01-01')
        ->and($cellMap['F6'])->toBe('date:2024-03-01')
        ->and($cellMap['G6'])->toBe('120 HRS')
        ->and($cellMap['H6'])->toBe('Volunteer Nurse')
        ->and($cellMap['A18'])->toBe('Disaster Risk Reduction Training')
        ->and($cellMap['G18'])->toBe('24 HRS')
        ->and($cellMap['H18'])->toBe('Technical')
        ->and($cellMap['I18'])->toBe('DepEd Region X')
        ->and($cellMap['A42'])->toBe('Public Speaking')
        ->and($cellMap['C42'])->toBe('Outstanding Volunteer 2024')
        ->and($cellMap['I42'])->toBe('Philippine Red Cross');
});

it('limits C3 rows to template capacity', function () {
    $handler = new PdsC3Handler;

    $voluntaryWork = [];
    for ($i = 1; $i <= 8; $i++) {
        $voluntaryWork[] = (object) ['organization' => "ORG {$i}"];
    }

    $learningAndDevelopment = [];
    for ($i = 1; $i <= 23; $i++) {
        $learningAndDevelopment[] = (object) ['training_title' => "L&D {$i}"];
    }

    $expertise = [];
    $awards = [];
    $affiliation = [];
    for ($i = 1; $i <= 9; $i++) {
        $expertise[] = (object) ['expertise' => "Skill {$i}"];
        $awards[] = (object) ['award_title' => "Award {$i}"];
        $affiliation[] = (object) ['affiliation' => "Org {$i}"];
    }

    $cellMap = $handler->buildCellMap(
        $voluntaryWork,
        $learningAndDevelopment,
        $expertise,
        $awards,
        $affiliation,
        fn ($value) => $value,
        fn ($value) => ($value === null || trim((string) $value) === '') ? 'N/A' : (string) $value
    );

    expect($cellMap['A11'])->toBe('ORG 6')
        ->and($cellMap)->not->toHaveKey('A12')
        ->and($cellMap['A38'])->toBe('L&D 21')
        ->and($cellMap)->not->toHaveKey('A39')
        ->and($cellMap['A48'])->toBe('Skill 7')
        ->and($cellMap['C48'])->toBe('Award 7')
        ->and($cellMap['I48'])->toBe('Org 7')
        ->and($cellMap)->not->toHaveKey('A49');
});

it('keeps empty C3 fields blank instead of N/A', function () {
    $handler = new PdsC3Handler;

    $cellMap = $handler->buildCellMap(
        [],
        [],
        [(object) ['expertise' => 'First Aid']],
        [],
        [],
        fn ($value) => $value,
        fn ($value) => ($value === null || trim((string) $value) === '') ? 'N/A' : (string) $value
    );

    expect($cellMap['A42'])->toBe('First Aid')
        ->and($cellMap['C42'])->toBe('N/A')
        ->and($cellMap['I42'])->toBe('N/A')
        ->and($cellMap)->not->toHaveKey('A43')
        ->and($cellMap)->toContain('N/A');
});
