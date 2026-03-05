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
    | Two-card back-to-back detection
    |--------------------------------------------------------------------------
    |
    | `two_card_ratio_threshold`: auto-detects if template likely contains
    | front+back side by side. Keep at 1.4 if your sizing is calibrated.
    | `force_two_cards`: set true to always duplicate overlays to 2nd card,
    | false to always render only one card, or null for auto-detect.
    |
    */
    'two_card_ratio_threshold' => (float) env('ID_CARD_TWO_CARD_RATIO_THRESHOLD', 1.4),
    'force_two_cards' => env('ID_CARD_FORCE_TWO_CARDS', true),

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

    'name_ttf_font' => env('ID_CARD_NAME_TTF_FONT', null),

    /*
    |--------------------------------------------------------------------------
    | Map job title (or role) to template filename (job_shorten)
    |--------------------------------------------------------------------------
    | If the employee's job_title matches a key, that PNG is used.
    | Example: 'Trainee' => 'TRAINEE', 'Administrative Aide I' => 'AIDVI'
    */
    'job_title_to_template' => [
        'Accounting III' => 'ACTIII',
        'Administrative Assistant II' => 'ADASII',
        'Administrative Assistant III' => 'ADASIII',
        'Administrative Aide VI' => 'AIDVI',
        'Trainee' => 'TRAINEE',
        // Add more mappings as needed to match your TEMPLATE ID/TEMPLATE filenames.
    ],

];
