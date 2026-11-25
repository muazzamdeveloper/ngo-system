<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Aadhaar checksum validation
    |--------------------------------------------------------------------------
    | Real Aadhaar numbers carry a Verhoeff check digit, which catches typing
    | mistakes. Set AADHAAR_CHECKSUM=false in .env only if a client wants to
    | accept any 12-digit number (for example while entering demo data).
    */
    'aadhaar_checksum' => (bool) env('AADHAAR_CHECKSUM', false),

];
