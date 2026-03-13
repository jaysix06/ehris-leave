<?php

use App\Http\Controllers\SelfService\WfhTimeInOutController;
use Tests\TestCase;

uses(TestCase::class);

it('keeps WFH PDF header and footer images from stretching', function () {
    $controller = new WfhTimeInOutController;
    $method = new ReflectionMethod(WfhTimeInOutController::class, 'buildWfhPdfHtml');
    $method->setAccessible(true);

    $html = $method->invoke(
        $controller,
        'data:image/jpeg;base64,'.base64_encode('header'),
        'data:image/jpeg;base64,'.base64_encode('footer'),
        '(March 01, 2026-March 11, 2026)',
        'Jane Doe',
        'Sample Station',
        [
            [
                'targeted_task' => 'Task A',
                'accomplishment' => 'Done',
                'date_range' => '03/01/2026-03/02/2026',
                'priority' => 'High',
            ],
        ]
    );

    expect($html)->toContain('.header-image {')
        ->and($html)->toContain('max-width: 100%;')
        ->and($html)->toContain('max-height: 1.53in;')
        ->and($html)->toContain('@page { margin: 2.05in 0.35in 1.10in 0.35in; }')
        ->and($html)->toContain('.footer-image {')
        ->and($html)->toContain('max-height: 0.99in;')
        ->and($html)->toContain('.page-border {')
        ->and($html)->toContain('top: -1.65in;')
        ->and($html)->toContain('left: 0.05in;')
        ->and($html)->toContain('right: 0.05in;')
        ->and($html)->toContain('bottom: -0.70in;')
        ->and($html)->toContain('.page-header {')
        ->and($html)->toContain('top: -1.52in;')
        ->and($html)->toContain('.page-footer {')
        ->and($html)->toContain('bottom: -0.70in;')
        ->and($html)->toContain('.page-content {')
        ->and($html)->toContain('margin: 1.08in 0 0;')
        ->and($html)->toContain('table {')
        ->and($html)->toContain('width: 96%;')
        ->and($html)->toContain('margin: 0 auto;')
        ->and($html)->toContain('thead { display: table-row-group; }')
        ->and($html)->toContain('.report-title-line {')
        ->and($html)->toContain('margin: 0 0.25in 0.08in;')
        ->and($html)->toContain('.employee-name {')
        ->and($html)->toContain('width: 96%;')
        ->and($html)->toContain('margin: 0.05in auto 0;')
        ->and($html)->toContain('text-align: left;')
        ->and($html)->toContain('.prepared-by {')
        ->and($html)->toContain('position: fixed;')
        ->and($html)->toContain('right: 0.25in;')
        ->and($html)->toContain('bottom: 0.58in;')
        ->and($html)->toContain('width: 2.55in;')
        ->and($html)->toContain('margin: 0;')
        ->and($html)->toContain('text-align: left;')
        ->and($html)->toContain('line-height: 1.2;')
        ->and($html)->toContain('break-inside: avoid;')
        ->and($html)->toContain('.table-block-continued {')
        ->and($html)->toContain('page-break-before: always;')
        ->and($html)->toContain('padding-top: 1.08in;')
        ->and($html)->toContain('.prepared-by-label {')
        ->and($html)->toContain('margin: 0 0 0.02in;')
        ->and($html)->toContain('.prepared-by-name {')
        ->and($html)->toContain('margin: 0.18in 0 0;')
        ->and($html)->toContain('font-weight: 700;')
        ->and($html)->toContain('Prepared by:')
        ->and($html)->toContain('page-break-inside: auto;')
        ->and($html)->toContain('tbody { page-break-inside: auto; }')
        ->and($html)->toContain('font-size: 12pt;')
        ->and($html)->toContain('font-family: "BookmanOldStyle";')
        ->and($html)->toContain('vertical-align: top;')
        ->and($html)->toContain('line-height: 1.25;')
        ->and($html)->toContain('white-space: pre-wrap;')
        ->and($html)->toContain('overflow-wrap: break-word;');

    expect($html)->not->toContain('font-family: "WFH Bookman", "Bookman Old Style", serif;');

    expect((bool) preg_match('/\.header-image\s*\{[^}]*\bwidth:\s*auto;/s', $html))->toBeTrue()
        ->and((bool) preg_match('/\.footer-image\s*\{[^}]*\bwidth:\s*auto;/s', $html))->toBeTrue()
        ->and((bool) preg_match('/\.header-image\s*\{\s*width:\s*100%;/s', $html))->toBeFalse()
        ->and((bool) preg_match('/\.footer-image\s*\{\s*width:\s*100%;/s', $html))->toBeFalse()
        ->and((bool) preg_match('/<hr class="report-title-line">\s*<p class="employee-name">/s', $html))->toBeTrue()
        ->and((bool) preg_match('/<div class="prepared-by">[\s\S]*<div class="page-content">/s', $html))->toBeTrue();
});

it('formats employee name with middle initial for PDF', function () {
    $controller = new WfhTimeInOutController;
    $method = new ReflectionMethod(WfhTimeInOutController::class, 'formatEmployeeDisplayName');
    $method->setAccessible(true);

    $nameWithMiddle = $method->invoke($controller, 'Juan', 'Santos', 'Dela Cruz');
    $nameWithoutMiddle = $method->invoke($controller, 'Juan', '', 'Dela Cruz');

    expect($nameWithMiddle)->toBe('Juan S. Dela Cruz')
        ->and($nameWithoutMiddle)->toBe('Juan Dela Cruz');
});

it('preserves multiline task and accomplishment text', function () {
    $controller = new WfhTimeInOutController;
    $method = new ReflectionMethod(WfhTimeInOutController::class, 'buildWfhPdfHtml');
    $method->setAccessible(true);

    $html = $method->invoke(
        $controller,
        null,
        null,
        '(March 01, 2026-March 11, 2026)',
        'Jane Doe',
        'Sample Station',
        [
            [
                'targeted_task' => "Target line 1\nTarget line 2",
                'accomplishment' => "Done line 1\nDone line 2",
                'date_range' => '03/01/2026-03/02/2026',
                'priority' => 'High',
            ],
        ]
    );

    expect($html)->toContain('Target line 1<br')
        ->and($html)->toContain('Target line 2')
        ->and($html)->toContain('Done line 1<br')
        ->and($html)->toContain('Done line 2');
});

it('creates page-broken table blocks when rows exceed page capacity', function () {
    $controller = new WfhTimeInOutController;
    $method = new ReflectionMethod(WfhTimeInOutController::class, 'buildWfhPdfHtml');
    $method->setAccessible(true);

    $tasks = [];
    for ($index = 0; $index < 20; $index++) {
        $tasks[] = [
            'targeted_task' => 'Task '.$index.' '.str_repeat('detail ', 10),
            'accomplishment' => 'Accomplishment '.$index.' '.str_repeat('output ', 9),
            'date_range' => '03/01/2026-03/02/2026',
            'priority' => 'High',
        ];
    }

    $html = $method->invoke(
        $controller,
        null,
        null,
        '(March 01, 2026-March 31, 2026)',
        'Jane Doe',
        'Sample Station',
        $tasks
    );

    expect(substr_count($html, '<table>'))->toBeGreaterThan(1)
        ->and($html)->toContain('class="table-block table-block-continued"');
});
