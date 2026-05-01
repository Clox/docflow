#!/usr/bin/env bash

set -e

HOST="127.0.0.1"
PORT="4321"
URL="http://${HOST}:${PORT}"
WORKERS="${PHP_CLI_SERVER_WORKERS:-4}"
OPEN_BROWSER="${DOCFLOW_OPEN_BROWSER:-1}"
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

start_detached() {
  local log_file="$1"
  shift

  if command -v setsid >/dev/null 2>&1; then
    setsid "$@" > "${log_file}" 2>&1 < /dev/null &
  else
    nohup "$@" > "${log_file}" 2>&1 < /dev/null &
  fi
  echo "$!"
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

SERVER_PID="$(start_detached "${LOG_FILE}" env PHP_CLI_SERVER_WORKERS="${WORKERS}" php -S "${HOST}:${PORT}" -t "${DOCROOT}")"
echo "${SERVER_PID}" > "${PID_FILE}"

DISPATCHER_PID="$(start_detached "${DISPATCHER_LOG_FILE}" php "${DISPATCHER_SCRIPT}")"
echo "${DISPATCHER_PID}" > "${DISPATCHER_PID_FILE}"

if [[ "${OPEN_BROWSER}" != "0" ]]; then
  if command -v xdg-open >/dev/null 2>&1; then
    xdg-open "${URL}" >/dev/null 2>&1 || true
  elif command -v open >/dev/null 2>&1; then
    open "${URL}" >/dev/null 2>&1 || true
  fi
fi

echo "PDF viewer started at ${URL}"
echo "PHP server PID: ${SERVER_PID}"
echo "Dispatcher PID: ${DISPATCHER_PID}"
echo "PHP workers: ${WORKERS}"
echo "Log file: ${LOG_FILE}"
echo "Dispatcher log file: ${DISPATCHER_LOG_FILE}"
