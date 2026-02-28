<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ID Card templates path
    |--------------------------------------------------------------------------
    |
    | Directory containing ID card template images (e.g. PNG). Can be an
    | absolute path or a path relative to the project root. If using the
    | default, copy your TEMPLATE ID/TEMPLATE contents into public/id-card-templates.
    |
    */

    'templates_path' => env('ID_CARD_TEMPLATES_PATH', null),

    /*
    |--------------------------------------------------------------------------
    | EODB ID BB template by employment status (like legacy eodb_idBB.php)
    |--------------------------------------------------------------------------
    | Casual → contractualEODBIDBB.png, Permanent → regularEODBIDBB.png,
    | otherwise → officialEODBIDBB.png. Overridden if job_shorten template exists.
    */
    'eodb_by_status' => [
        'Casual' => 'contractualEODBIDBB.png',
        'Permanent' => 'regularEODBIDBB.png',
        'default' => 'officialEODBIDBB.png',
    ],

    /*
    |--------------------------------------------------------------------------
    | EODB ID BB fallback template
    |--------------------------------------------------------------------------
    | Used when status-based and job-based templates are not found.
    */
    'eodb_id_bb_template' => env('ID_CARD_EODB_TEMPLATE', 'EODBBB.png'),

    /*
    |--------------------------------------------------------------------------
    | Map role to template filename (checked first so role overrides job title)
    |--------------------------------------------------------------------------
    | Use this so the correct template is used per role (e.g. System Admin).
    | If the role matches, that template is used and job-specific templates are skipped.
    */
    'role_to_template' => [
        'System Admin' => 'officialEODBIDBB.png',
        'Admin System' => 'officialEODBIDBB.png',
        'System Administrator' => 'officialEODBIDBB.png',
        // Add more: 'Trainee' => 'TRAINEE.png', etc.
    ],

    /*
    |--------------------------------------------------------------------------
    | Map job title (or role) to template filename (job_shorten)
    |--------------------------------------------------------------------------
    | If the employee's job_title matches a key, that PNG is used.
    | Example: 'Trainee' => 'TRAINEE', 'Administrative Aide I' => 'AIDVI'
    */
    'job_title_to_template' => [
        'Trainee' => 'TRAINEE',
        // Add more mappings as needed to match your TEMPLATE ID/TEMPLATE filenames.
    ],

];
