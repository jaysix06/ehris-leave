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
        ->and($html)->toContain('.footer-image {')
        ->and($html)->toContain('max-height: 0.99in;')
        ->and($html)->toContain('.page-border {')
        ->and($html)->toContain('top: -1.25in;')
        ->and($html)->toContain('left: 0.25in;')
        ->and($html)->toContain('right: 0.25in;')
        ->and($html)->toContain('bottom: -0.18in;')
        ->and($html)->toContain('.page-header {')
        ->and($html)->toContain('top: -1.72in;')
        ->and($html)->toContain('.page-footer {')
        ->and($html)->toContain('bottom: -0.62in;')
        ->and($html)->toContain('.page-content {')
        ->and($html)->toContain('margin-top: 0.18in;');

    expect((bool) preg_match('/\.header-image\s*\{[^}]*\bwidth:\s*auto;/s', $html))->toBeTrue()
        ->and((bool) preg_match('/\.footer-image\s*\{[^}]*\bwidth:\s*auto;/s', $html))->toBeTrue()
        ->and((bool) preg_match('/\.header-image\s*\{\s*width:\s*100%;/s', $html))->toBeFalse()
        ->and((bool) preg_match('/\.footer-image\s*\{\s*width:\s*100%;/s', $html))->toBeFalse();
});
