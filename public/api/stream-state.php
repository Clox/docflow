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

$requestedAfterEventId = filter_var($_GET['afterEventId'] ?? null, FILTER_VALIDATE_INT);
if ($requestedAfterEventId === false || $requestedAfterEventId === null || $requestedAfterEventId < 0) {
    $requestedAfterEventId = 0;
}

$headerAfterEventId = filter_var($_SERVER['HTTP_LAST_EVENT_ID'] ?? null, FILTER_VALIDATE_INT);
if ($headerAfterEventId === false || $headerAfterEventId === null || $headerAfterEventId < 0) {
    $headerAfterEventId = 0;
}

$lastEventId = max((int) $requestedAfterEventId, (int) $headerAfterEventId);
$lastKeepaliveAt = time();

while (!connection_aborted()) {
    try {
        $config = load_config();
        ensure_job_dispatcher_running($config);
        $events = read_job_events_since($lastEventId);
        if (count($events) > 0) {
            foreach ($events as $event) {
                if (!is_array($event)) {
                    continue;
                }

                $eventId = isset($event['id']) ? (int) $event['id'] : 0;
                if ($eventId <= $lastEventId) {
                    continue;
                }

                $encodedEvent = json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if (!is_string($encodedEvent)) {
                    throw new RuntimeException('Kunde inte serialisera jobbevent');
                }

                echo 'id: ' . $eventId . "\n";
                echo "event: job\n";
                echo 'data: ' . $encodedEvent . "\n\n";
                $lastEventId = $eventId;
            }

            $lastKeepaliveAt = time();
            @ob_flush();
            flush();
        } elseif ((time() - $lastKeepaliveAt) >= 10) {
            $keepalivePayload = json_encode([
                'at' => gmdate(DATE_ATOM),
                'lastEventId' => $lastEventId,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (!is_string($keepalivePayload)) {
                throw new RuntimeException('Kunde inte serialisera keepalive');
            }

            echo "event: keepalive\n";
            echo 'data: ' . $keepalivePayload . "\n\n";
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
