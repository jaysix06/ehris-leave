<?php

use App\Http\Controllers\MyDetails\PdsTemplatePathResolver;

it('returns the first existing path from candidates', function () {
    $resolver = new PdsTemplatePathResolver;

    $first = tempnam(sys_get_temp_dir(), 'pds-first-');
    $second = tempnam(sys_get_temp_dir(), 'pds-second-');

    expect($resolver->resolveExistingPath(['', 'C:\\does-not-exist.xlsx', $first, $second]))
        ->toBe($first);

    if (is_file($first)) {
        unlink($first);
    }
    if (is_file($second)) {
        unlink($second);
    }
});

it('returns null when no candidate exists', function () {
    $resolver = new PdsTemplatePathResolver;

    expect($resolver->resolveExistingPath(['', 'C:\\missing-a.xlsx', 'C:\\missing-b.xlsx']))
        ->toBeNull();
});
