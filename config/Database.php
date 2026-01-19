<?php
// Ensure constants are available, though they should be if app.php is loaded.
// Framework Loader::Config('Database') includes this file.
return [
    'engine' => 'mysql',
    'driver' => 'pdo',
    'host' => DB_HOST,
    'port' => DB_PORT,
    'user' => DB_USER,
    'password' => DB_PASS,
    'database' => 'auth_server', // Hardcoding auth_server to be sure, or use DB_NAME
    'timeout' => 2,
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
