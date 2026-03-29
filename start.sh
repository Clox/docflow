#!/usr/bin/env bash

set -e

HOST="127.0.0.1"
PORT="4321"
URL="http://${HOST}:${PORT}"
WORKERS="${PHP_CLI_SERVER_WORKERS:-4}"
DOCROOT="public"
LOG_FILE="/tmp/pdf-viewer-php.log"
PID_FILE="/tmp/docflow-php-server.pid"
DISPATCHER_SCRIPT="scripts/job-dispatcher.php"
DISPATCHER_LOG_FILE="/tmp/docflow-job-dispatcher.log"
DISPATCHER_PID_FILE="/tmp/docflow-job-dispatcher.pid"

stop_server_pid() {
  local pid="$1"
  if [[ -z "${pid}" ]] || ! [[ "${pid}" =~ ^[0-9]+$ ]]; then
    return 0
  fi

  if ! kill -0 "${pid}" >/dev/null 2>&1; then
    return 0
  fi

  kill "${pid}" >/dev/null 2>&1 || true
  for _ in {1..20}; do
    if ! kill -0 "${pid}" >/dev/null 2>&1; then
      return 0
    fi
    sleep 0.1
  done

  kill -9 "${pid}" >/dev/null 2>&1 || true
}

if [[ -f "${PID_FILE}" ]]; then
  EXISTING_PID="$(cat "${PID_FILE}" 2>/dev/null || true)"
  stop_server_pid "${EXISTING_PID}"
  rm -f "${PID_FILE}"
fi

if [[ -f "${DISPATCHER_PID_FILE}" ]]; then
  EXISTING_DISPATCHER_PID="$(cat "${DISPATCHER_PID_FILE}" 2>/dev/null || true)"
  stop_server_pid "${EXISTING_DISPATCHER_PID}"
  rm -f "${DISPATCHER_PID_FILE}"
fi

while IFS= read -r pid; do
  [[ -n "${pid}" ]] || continue
  stop_server_pid "${pid}"
done < <(pgrep -f "php -S ${HOST}:${PORT} -t ${DOCROOT}" || true)

while IFS= read -r pid; do
  [[ -n "${pid}" ]] || continue
  stop_server_pid "${pid}"
done < <(pgrep -f "${DISPATCHER_SCRIPT}" || true)

./scripts/migrate.php
php ./scripts/generate_chrome_extension_manifest.php "${URL}"

PHP_CLI_SERVER_WORKERS="${WORKERS}" php -S "${HOST}:${PORT}" -t "${DOCROOT}" > "${LOG_FILE}" 2>&1 &
SERVER_PID=$!
echo "${SERVER_PID}" > "${PID_FILE}"

php "${DISPATCHER_SCRIPT}" > "${DISPATCHER_LOG_FILE}" 2>&1 &
DISPATCHER_PID=$!
echo "${DISPATCHER_PID}" > "${DISPATCHER_PID_FILE}"

if command -v xdg-open >/dev/null 2>&1; then
  xdg-open "${URL}" >/dev/null 2>&1 || true
elif command -v open >/dev/null 2>&1; then
  open "${URL}" >/dev/null 2>&1 || true
fi

echo "PDF viewer started at ${URL}"
echo "PHP server PID: ${SERVER_PID}"
echo "Dispatcher PID: ${DISPATCHER_PID}"
echo "PHP workers: ${WORKERS}"
echo "Log file: ${LOG_FILE}"
echo "Dispatcher log file: ${DISPATCHER_LOG_FILE}"
