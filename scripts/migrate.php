#!/usr/bin/env php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/autoload.php';

use Docflow\Database\Connection;
use Docflow\Database\MigrationRunner;

try {
    if (!extension_loaded('pdo_sqlite')) {
        throw new RuntimeException('Missing PHP extension: pdo_sqlite');
    }

    $pdo = Connection::make();
    $runner = new MigrationRunner($pdo, __DIR__ . '/../database/migrations');
    $result = $runner->migrate();
    require_once __DIR__ . '/migrate-filename-templates-v2.php';
    $filenameTemplateMigration = migrate_stored_filename_templates_v2($pdo);

    echo 'SQLite DB: ' . Connection::databasePath() . PHP_EOL;
    echo 'Filename templates v2: ' . json_encode(
        $filenameTemplateMigration,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ) . PHP_EOL;

    $applied = is_array($result['applied'] ?? null) ? $result['applied'] : [];
    if (count($applied) === 0) {
        echo 'No new migrations to apply.' . PHP_EOL;
        exit(0);
    }

    echo 'Applied migrations:' . PHP_EOL;
    foreach ($applied as $name) {
        if (is_string($name)) {
            echo ' - ' . $name . PHP_EOL;
        }
    }

    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Migration failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
