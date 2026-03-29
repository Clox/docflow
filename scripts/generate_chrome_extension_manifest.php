#!/usr/bin/env php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../public/api/_functions.php';

function docflow_extension_origin_from_cli(array $argv): string
{
    $arg = isset($argv[1]) && is_string($argv[1]) ? trim($argv[1]) : '';
    if ($arg !== '') {
        return rtrim($arg, '/');
    }

    $env = getenv('DOCFLOW_PUBLIC_ORIGIN');
    if (is_string($env) && trim($env) !== '') {
        return rtrim(trim($env), '/');
    }

    return 'http://127.0.0.1:4321';
}

function docflow_extension_manifest_payload_for_origin(string $origin): array
{
    return [
        'manifest_version' => 3,
        'name' => 'Docflow Chrome Connector',
        'description' => 'Docflows koppling till Swedbank för BG/PG-uppslag.',
        'version' => docflow_chrome_extension_version(),
        'key' => docflow_chrome_extension_manifest_key(),
        'background' => [
            'service_worker' => 'background.js',
        ],
        'permissions' => ['tabs', 'scripting'],
        'host_permissions' => [
            'https://online.swedbank.se/*',
            'https://www.swedbank.se/*',
        ],
        'externally_connectable' => [
            'matches' => [$origin . '/*'],
        ],
    ];
}

function docflow_extension_readme_for_origin(string $origin): string
{
    return implode(PHP_EOL, [
        'Docflow Chrome Connector',
        '========================',
        '',
        'Installera tillägget via chrome://extensions och "Läs in opaketerat" / "Load unpacked".',
        '',
        'Den här mappen är byggd för följande Docflow-adress:',
        $origin,
        '',
        'Om Docflow körs på en annan värd eller port behöver manifest.json genereras om.',
    ]) . PHP_EOL;
}

$origin = docflow_extension_origin_from_cli($argv);
$extensionDir = docflow_chrome_extension_directory();

if (!is_dir($extensionDir)) {
    fwrite(STDERR, "Extension directory not found: {$extensionDir}\n");
    exit(1);
}

$manifestJson = json_encode(
    docflow_extension_manifest_payload_for_origin($origin),
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);

if (!is_string($manifestJson) || $manifestJson === '') {
    fwrite(STDERR, "Could not generate manifest JSON.\n");
    exit(1);
}

$manifestPath = $extensionDir . '/manifest.json';
$readmePath = $extensionDir . '/README.txt';

if (file_put_contents($manifestPath, $manifestJson . PHP_EOL) === false) {
    fwrite(STDERR, "Could not write manifest: {$manifestPath}\n");
    exit(1);
}

if (file_put_contents($readmePath, docflow_extension_readme_for_origin($origin)) === false) {
    fwrite(STDERR, "Could not write README: {$readmePath}\n");
    exit(1);
}

fwrite(STDOUT, "Generated Chrome extension manifest for {$origin}\n");
