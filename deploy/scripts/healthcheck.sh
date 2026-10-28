#!/usr/bin/env bash
set -Eeuo pipefail
port="${HTTP_PORT:-8088}"
url="${HEALTHCHECK_URL:-http://127.0.0.1:${port}/up}"
attempts="${HEALTHCHECK_ATTEMPTS:-30}"
for ((i=1; i<=attempts; i++)); do
  if curl --fail --silent --show-error --max-time 5 --output /dev/null "$url"; then
    echo "Healthy: $url"
    exit 0
  fi
  sleep 2
done
echo "Unhealthy after $attempts attempts: $url" >&2
exit 1
