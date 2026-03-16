<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

ignore_user_abort(true);
set_time_limit(0);

while (ob_get_level() > 0) {
    ob_end_flush();
}

header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Accel-Buffering: no');

echo "retry: 2000\n\n";
@ob_flush();
flush();

$lastSignature = '';
$lastKeepaliveAt = 0;
$startedAt = time();

while (!connection_aborted() && (time() - $startedAt) < 55) {
    try {
        $config = load_config();
        $payload = build_jobs_state_payload($config);
        $encodedPayload = encode_jobs_state_payload($payload);
        $signature = jobs_state_signature($encodedPayload);
        if ($signature !== $lastSignature) {
            $envelopePayload = ['jobsSig' => $signature] + $payload;
            $encodedEnvelope = json_encode($envelopePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (!is_string($encodedEnvelope)) {
                throw new RuntimeException('Kunde inte serialisera state');
            }
            echo "event: state\n";
            echo 'data: ' . $encodedEnvelope . "\n\n";
            $lastSignature = $signature;
            $lastKeepaliveAt = time();
            @ob_flush();
            flush();
        } elseif ((time() - $lastKeepaliveAt) >= 10) {
            echo ": keepalive\n\n";
            $lastKeepaliveAt = time();
            @ob_flush();
            flush();
        }
    } catch (Throwable $e) {
        $errorPayload = json_encode([
            'error' => $e->getMessage(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (is_string($errorPayload)) {
            echo "event: error\n";
            echo 'data: ' . $errorPayload . "\n\n";
            @ob_flush();
            flush();
        }
        break;
    }

    usleep(750000);
}
