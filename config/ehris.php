<?php

return [
    // Admin who activates users logs in as gavino.tan@deped.gov.ph.
    // New-user registration notifications are sent to admin_email (set in .env, e.g. bulokjeam@gmail.com).
    'admin_email' => env('EHRIS_ADMIN_EMAIL', 'bulokjeam@gmail.com'),
    'admin_name' => env('EHRIS_ADMIN_NAME', 'EHRIS Administrator'),
    'wfh_pdf_name_ttf_font' => env('WFH_PDF_NAME_TTF_FONT'),

    // Role => home path after login. Roles come from tbl_role; any role not listed here
    // redirects to the default fortify.home (/dashboard).
    'role_home' => [
        'Employee' => '/dashboard',
        'HR Manager' => '/dashboard',
        'AO Manager' => '/dashboard',
        'SDS Manager' => '/dashboard',
        'System Admin' => '/dashboard',
        'Teacher' => '/dashboard',
        'Reporting Manager' => '/dashboard',
        'HR Staff' => '/dashboard',
        'HRLD Manager' => '/dashboard',
        'HRLD Staff' => '/dashboard',
        'HRDD Manager' => '/dashboard',
        'HRDD Staff' => '/dashboard',
        'ICT Coordinator' => '/dashboard',
    ],
];
