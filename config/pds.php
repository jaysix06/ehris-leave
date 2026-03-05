<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PDS template override path
    |--------------------------------------------------------------------------
    |
    | Absolute path to a preferred PDS template file. If not set, the resolver
    | falls back to known storage/public/download locations.
    |
    */
    'template_path' => env('PDS_TEMPLATE_PATH', ''),

    /*
    |--------------------------------------------------------------------------
    | User profile path for Windows download fallback
    |--------------------------------------------------------------------------
    |
    | Kept in config so application code avoids direct env() calls.
    |
    */
    'user_profile' => env('USERPROFILE', ''),

    /*
    |--------------------------------------------------------------------------
    | Candidate filenames in Downloads
    |--------------------------------------------------------------------------
    */
    'download_filenames' => [
        'ANNEX-H-1-CS-Form-No.-212-Revised-2025-Personal-Data-Sheet (1).xlsx',
        'ANNEX-H-1-CS-Form-No.-212-Revised-2025-Personal-Data-Sheet.xlsx',
        'deped-pds-template.xlsx',
    ],
];
