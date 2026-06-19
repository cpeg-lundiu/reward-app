<?php

declare(strict_types=1);

/**
 * Loads database/schema.sql into the configured database via PDO.
 * Used instead of the `mysql` CLI (which isn't installed here).
 *
 * Usage: php database/migrate.php
 */

require __DIR__ . '/../vendor/autoload.php';

$settings = require __DIR__ . '/../config/settings.php';
$db = $settings['db'];

$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $db['host'], $db['port'], $db['name'], $db['charset']);

try {
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "Could not connect to the database: {$e->getMessage()}\n");
    exit(1);
}

$sql = file_get_contents(__DIR__ . '/schema.sql');
if ($sql === false) {
    fwrite(STDERR, "Could not read schema.sql\n");
    exit(1);
}

try {
    // PDO can execute the multi-statement script in one go for the mysql driver.
    $pdo->exec($sql);
    echo "Schema loaded successfully into '{$db['name']}'.\n";

    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo 'Tables: ' . implode(', ', $tables) . "\n";
} catch (PDOException $e) {
    fwrite(STDERR, "Migration failed: {$e->getMessage()}\n");
    exit(1);
}
