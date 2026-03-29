<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

function current_request_origin(): string
{
    $https = $_SERVER['HTTPS'] ?? '';
    $scheme = (is_string($https) && $https !== '' && strtolower($https) !== 'off') ? 'https' : 'http';
    $host = is_string($_SERVER['HTTP_HOST'] ?? null) ? trim((string) $_SERVER['HTTP_HOST']) : '';
    if ($host === '') {
        $host = '127.0.0.1';
    }
    return $scheme . '://' . $host;
}

function docflow_extension_manifest_payload(): array
{
    $origin = current_request_origin();
    return [
        'manifest_version' => 3,
        'name' => 'Docflow Chrome Connector',
        'description' => 'Första versionen av Docflows koppling till Swedbank och BG/PG-uppslag.',
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

function docflow_extension_readme_text(): string
{
    $origin = current_request_origin();
    return implode(PHP_EOL, [
        'Docflow Chrome Connector',
        '========================',
        '',
        '1. Packa upp zip-filen i en egen mapp.',
        '2. Öppna chrome://extensions.',
        '3. Slå på Developer mode / Utvecklarläge.',
        '4. Klicka på Load unpacked / Läs in okomprimerat tillägg.',
        '5. Välj den uppackade mappen.',
        '',
        'Det här paketet är byggt för den här Docflow-adressen:',
        $origin,
        '',
        'Om du byter värd eller port i Docflow behöver du ladda ner och installera tillägget igen.',
    ]) . PHP_EOL;
}

$backgroundPath = dirname(__DIR__, 2) . '/chrome-extension/docflow-extension/background.js';
if (!is_file($backgroundPath)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Kunde inte hitta extension-filen background.js.';
    exit;
}

$backgroundSource = file_get_contents($backgroundPath);
if (!is_string($backgroundSource) || $backgroundSource === '') {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Kunde inte läsa extension-filen background.js.';
    exit;
}

$manifestJson = json_encode(docflow_extension_manifest_payload(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if (!is_string($manifestJson)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Kunde inte bygga manifest.json.';
    exit;
}

try {
    $randomSuffix = bin2hex(random_bytes(8));
} catch (Throwable $e) {
    $randomSuffix = uniqid('', true);
}
$tempDir = sys_get_temp_dir() . '/docflow-extension-' . $randomSuffix;
if (!@mkdir($tempDir, 0700, true) && !is_dir($tempDir)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Kunde inte skapa tillfällig extensionsmapp.';
    exit;
}

$tempZip = $tempDir . '.zip';
file_put_contents($tempDir . '/manifest.json', $manifestJson . PHP_EOL);
file_put_contents($tempDir . '/background.js', $backgroundSource);
file_put_contents($tempDir . '/README.txt', docflow_extension_readme_text());

if (class_exists('ZipArchive')) {
    $zip = new ZipArchive();
    if ($zip->open($tempZip, ZipArchive::OVERWRITE) !== true) {
        @unlink($tempZip);
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Kunde inte öppna zip-arkiv.';
        exit;
    }
    $zip->addFile($tempDir . '/manifest.json', 'manifest.json');
    $zip->addFile($tempDir . '/background.js', 'background.js');
    $zip->addFile($tempDir . '/README.txt', 'README.txt');
    $zip->close();
} else {
    $zipBinary = trim((string) shell_exec('command -v zip 2>/dev/null'));
    if ($zipBinary === '') {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Varken ZipArchive eller zip-kommandot finns tillgängligt.';
        exit;
    }
    $command = sprintf(
        'cd %s && %s -qr %s manifest.json background.js README.txt 2>&1',
        escapeshellarg($tempDir),
        escapeshellarg($zipBinary),
        escapeshellarg($tempZip)
    );
    exec($command, $output, $exitCode);
    if ($exitCode !== 0 || !is_file($tempZip)) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Kunde inte skapa zip-arkiv med zip-kommandot.';
        exit;
    }
}

$downloadName = 'docflow-chrome-extension-' . docflow_chrome_extension_version() . '.zip';
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . (string) filesize($tempZip));
readfile($tempZip);
@unlink($tempZip);
@unlink($tempDir . '/manifest.json');
@unlink($tempDir . '/background.js');
@unlink($tempDir . '/README.txt');
@rmdir($tempDir);
