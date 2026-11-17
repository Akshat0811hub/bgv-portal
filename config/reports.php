<?php

return [
    // Large responses are reassembled in the browser on serverless hosting.
    'chunked_downloads' => env('REPORT_CHUNKED_DOWNLOADS', (bool) (getenv('VERCEL') || getenv('VERCEL_ENV'))),
    // auto: prefer explicitly configured Gotenberg; otherwise use local LibreOffice.
    'converter' => env('REPORT_DOCUMENT_CONVERTER', 'auto'),
    'libreoffice_binary' => env('REPORT_LIBREOFFICE_BINARY'),
    'qpdf_binary' => env('REPORT_QPDF_BINARY'),
    'conversion_timeout' => (int) env('REPORT_CONVERSION_TIMEOUT', 90),
    'max_converted_bytes' => (int) env('REPORT_MAX_CONVERTED_BYTES', 104857600),
    'gotenberg' => [
        // Only configure an instance controlled/trusted by your organization.
        'url' => env('REPORT_GOTENBERG_URL'),
        'username' => env('REPORT_GOTENBERG_USERNAME'),
        'password' => env('REPORT_GOTENBERG_PASSWORD'),
        'token' => env('REPORT_GOTENBERG_TOKEN'),
    ],
];
