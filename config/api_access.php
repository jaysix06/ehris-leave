<?php

return [
    /*
    |--------------------------------------------------------------------------
    | External API access key
    |--------------------------------------------------------------------------
    |
    | Used for simple server-to-server access. Send this value in the
    | X-API-KEY request header to access protected API endpoints.
    |
    */
    'key' => env('EXTERNAL_API_KEY', ''),
];
