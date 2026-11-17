<?php

// Forward Vercel requests to Laravel's public/index.php
// Setup writable storage directory in /tmp for serverless runtime (AWS Lambda / Vercel)
$storagePath = '/tmp/storage';

if (!is_dir('/tmp/views')) {
    @mkdir('/tmp/views', 0755, true);
}
if (!is_dir($storagePath)) {
    @mkdir($storagePath . '/framework/views', 0755, true);
    @mkdir($storagePath . '/framework/cache/data', 0755, true);
    @mkdir($storagePath . '/framework/sessions', 0755, true);
    @mkdir($storagePath . '/logs', 0755, true);
    @mkdir($storagePath . '/app/public', 0755, true);
}

// Copy pre-seeded initial SQLite database if external DB is not set
if (empty(getenv('DB_HOST')) && (!file_exists('/tmp/database.sqlite') || filesize('/tmp/database.sqlite') === 0)) {
    $initialDb = __DIR__ . '/../database/initial_db.sqlite';
    $defaultDb = __DIR__ . '/../database/database.sqlite';
    
    if (file_exists($initialDb)) {
        @copy($initialDb, '/tmp/database.sqlite');
    } elseif (file_exists($defaultDb)) {
        @copy($defaultDb, '/tmp/database.sqlite');
    } else {
        @touch('/tmp/database.sqlite');
    }
}

// Set environment variables for serverless execution
putenv('VIEW_COMPILED_PATH=/tmp/views');
putenv('APP_SERVICES_CACHE=' . $storagePath . '/services.php');
putenv('APP_PACKAGES_CACHE=' . $storagePath . '/packages.php');
putenv('APP_CONFIG_CACHE=' . $storagePath . '/config.php');
putenv('APP_ROUTES_CACHE=' . $storagePath . '/routes.php');
putenv('APP_EVENTS_CACHE=' . $storagePath . '/events.php');

if (empty(getenv('DB_HOST'))) {
    putenv('DB_CONNECTION=sqlite');
    putenv('DB_DATABASE=/tmp/database.sqlite');
    $_ENV['DB_CONNECTION'] = 'sqlite';
    $_ENV['DB_DATABASE'] = '/tmp/database.sqlite';
}

require __DIR__ . '/../public/index.php';
