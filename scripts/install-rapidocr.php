#!/usr/bin/env php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../public/api/_bootstrap.php';

function update_install_status(string $state, string $message, array $extra = []): void
{
    $existing = load_json_file(rapidocr_install_status_path()) ?? [];
    $payload = array_merge([
        'state' => $state,
        'message' => $message,
        'startedAt' => is_string($existing['startedAt'] ?? null) && trim((string) $existing['startedAt']) !== ''
            ? trim((string) $existing['startedAt'])
            : now_iso(),
        'finishedAt' => '',
    ], $extra);

    write_rapidocr_install_status($payload);
}

function append_install_log(string $text): void
{
    @file_put_contents(rapidocr_install_log_path(), $text, FILE_APPEND);
}

function run_install_command(string $label, string $command): void
{
    append_install_log("\n[" . now_iso() . "] " . $label . "\n$command\n");
    exec($command . ' >> ' . escapeshellarg(rapidocr_install_log_path()) . ' 2>&1', $output, $exitCode);
    if ($exitCode !== 0) {
        throw new RuntimeException($label . ' misslyckades. Se data/rapidocr-install.log.');
    }
}

ensure_directory(DATA_DIR);
ensure_directory(dirname(rapidocr_local_venv_dir()));

$lockHandle = fopen(rapidocr_install_lock_path(), 'c+');
if ($lockHandle === false) {
    exit(1);
}

if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
    fclose($lockHandle);
    exit(0);
}

file_put_contents(rapidocr_install_pid_path(), (string) getmypid());
file_put_contents(rapidocr_install_log_path(), '');

register_shutdown_function(static function () use ($lockHandle): void {
    @unlink(rapidocr_install_pid_path());
    @flock($lockHandle, LOCK_UN);
    @fclose($lockHandle);
});

try {
    $python = python_command_path();
    if ($python === null) {
        throw new RuntimeException('Python 3 hittades inte.');
    }
    if (!python_can_create_venv($python)) {
        throw new RuntimeException('Python 3 saknar venv-stöd. Installera python3-venv.');
    }

    update_install_status('installing', 'Skapar lokal Python-miljö...');
    run_install_command(
        'Create venv',
        escapeshellarg($python)
        . ' -m venv '
        . escapeshellarg(rapidocr_local_venv_dir())
    );

    $localPython = rapidocr_local_python_path();
    if (!is_file($localPython)) {
        throw new RuntimeException('Den lokala Python-miljön skapades inte korrekt.');
    }

    update_install_status('installing', 'Installerar/uppgraderar pip i lokal miljö...');
    run_install_command(
        'Upgrade pip',
        escapeshellarg($localPython)
        . ' -m pip install --upgrade pip'
    );

    update_install_status('installing', 'Installerar RapidOCR lokalt...');
    run_install_command(
        'Install RapidOCR',
        escapeshellarg($localPython)
        . ' -m pip install rapidocr onnxruntime'
    );

    update_install_status('installed', 'RapidOCR installerat lokalt.', [
        'finishedAt' => now_iso(),
    ]);
} catch (Throwable $e) {
    update_install_status('failed', $e->getMessage(), [
        'finishedAt' => now_iso(),
    ]);
    append_install_log("\n[" . now_iso() . "] ERROR: " . $e->getMessage() . "\n");
    exit(1);
}
