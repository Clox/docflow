<?php
declare(strict_types=1);

namespace Docflow\Database;

use PDO;

final class Connection
{
    public static function databasePath(): string
    {
        return dirname(__DIR__, 2) . '/data/docflow.sqlite';
    }

    public static function make(?string $databasePath = null): PDO
    {
        $path = is_string($databasePath) && trim($databasePath) !== ''
            ? $databasePath
            : self::databasePath();

        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $pdo = new PDO('sqlite:' . $path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $pdo->exec('PRAGMA foreign_keys = ON');

        return $pdo;
    }
}
