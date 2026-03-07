#!/usr/bin/env bash

set -e

HOST="127.0.0.1"
PORT="4321"
URL="http://${HOST}:${PORT}"

php -S "${HOST}:${PORT}" -t public > /tmp/pdf-viewer-php.log 2>&1 &
SERVER_PID=$!

if command -v xdg-open >/dev/null 2>&1; then
  xdg-open "${URL}" >/dev/null 2>&1 || true
elif command -v open >/dev/null 2>&1; then
  open "${URL}" >/dev/null 2>&1 || true
fi

echo "PDF viewer started at ${URL}"
echo "PHP server PID: ${SERVER_PID}"
echo "Log file: /tmp/pdf-viewer-php.log"
