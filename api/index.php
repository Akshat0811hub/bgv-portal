<?php

// Ensure Symfony/Laravel Request sees standard root entry point instead of /api/index.php
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/../public/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

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
    @mkdir($storagePath . '/app/private', 0755, true);
}

// Set environment variables for serverless execution
putenv('STORAGE_DIR=' . $storagePath . '/app/private');
putenv('PUBLIC_STORAGE_DIR=' . $storagePath . '/app/public');
putenv('VIEW_COMPILED_PATH=/tmp/views');
putenv('APP_SERVICES_CACHE=' . $storagePath . '/services.php');
putenv('APP_PACKAGES_CACHE=' . $storagePath . '/packages.php');
putenv('APP_CONFIG_CACHE=' . $storagePath . '/config.php');
putenv('APP_ROUTES_CACHE=' . $storagePath . '/routes.php');
putenv('APP_EVENTS_CACHE=' . $storagePath . '/events.php');

// Use external MySQL/PostgreSQL when configured via Vercel Environment Variables;
// fall back to /tmp/database.sqlite if no external DB is configured or reachable.
$mysqlHost = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? '');
$mysqlPort = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '3306');
$mysqlDb   = getenv('DB_DATABASE') ?: ($_ENV['DB_DATABASE'] ?? '');
$mysqlUser = getenv('DB_USERNAME') ?: ($_ENV['DB_USERNAME'] ?? '');
$mysqlPass = getenv('DB_PASSWORD') ?: ($_ENV['DB_PASSWORD'] ?? '');
$dbUrl     = getenv('DB_URL') ?: ($_ENV['DB_URL'] ?? '');

$externalReady = false;

if ($dbUrl !== '') {
    $externalReady = true;
} elseif ($mysqlHost !== '' && $mysqlDb !== '' && $mysqlUser !== '' && extension_loaded('pdo_mysql')) {
    try {
        $dsn = "mysql:host={$mysqlHost};port={$mysqlPort};dbname={$mysqlDb};charset=utf8mb4";
        new PDO($dsn, $mysqlUser, $mysqlPass, [
            PDO::ATTR_TIMEOUT => 3,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $externalReady = true;
    } catch (\Throwable $e) {
        $externalReady = false;
    }
}

if ($externalReady) {
    if (empty(getenv('DB_CONNECTION')) && empty($_ENV['DB_CONNECTION'])) {
        putenv('DB_CONNECTION=mysql');
        $_ENV['DB_CONNECTION'] = 'mysql';
    }
} else {
    if (!file_exists('/tmp/database.sqlite') || filesize('/tmp/database.sqlite') === 0) {
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
    putenv('DB_CONNECTION=sqlite');
    putenv('DB_DATABASE=/tmp/database.sqlite');
    $_ENV['DB_CONNECTION'] = 'sqlite';
    $_ENV['DB_DATABASE'] = '/tmp/database.sqlite';
}

require __DIR__ . '/../public/index.php';
