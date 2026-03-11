<?php

return [
    // Admin who activates users logs in as gavino.tan@deped.gov.ph.
    // New-user registration notifications are sent to admin_email (set in .env, e.g. bulokjeam@gmail.com).
    'admin_email' => env('EHRIS_ADMIN_EMAIL', 'bulokjeam@gmail.com'),
    'admin_name' => env('EHRIS_ADMIN_NAME', 'EHRIS Administrator'),
    'wfh_pdf_name_ttf_font' => env('WFH_PDF_NAME_TTF_FONT'),
];
