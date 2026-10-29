#!/usr/bin/env bash
set -Eeuo pipefail
port="${HTTP_PORT:-8088}"
health_url="${HEALTHCHECK_URL:-http://127.0.0.1:${port}/up}"
[[ "$health_url" == */up ]] || { echo "HEALTHCHECK_URL must end in /up" >&2; exit 1; }
home_url="${HEALTHCHECK_HOME_URL:-${health_url%/up}/}"
attempts="${HEALTHCHECK_ATTEMPTS:-30}"
html_file="$(mktemp)"
trap 'rm -f -- "$html_file"' EXIT
last_error="Application did not become ready"

for ((i=1; i<=attempts; i++)); do
  if status="$(curl --silent --show-error --max-time 5 --output /dev/null --write-out '%{http_code}' "$health_url")" && [[ "$status" == 200 ]]; then
    if status="$(curl --silent --show-error --max-time 10 --output "$html_file" --write-out '%{http_code}' "$home_url")" && [[ "$status" == 200 ]]; then
      if css_reference="$(grep -Eo '(https?://[^"<>[:space:]]+)?/build/assets/[^"<>[:space:]]+\.css([?][^"<>[:space:]]*)?' "$html_file" | sed -n '1p')" && [[ -n "$css_reference" ]]; then
        case "$css_reference" in
          https://*)
            if [[ "$home_url" == http://* ]]; then
              echo "Invalid HTTPS CSS URL on the local HTTP endpoint: $css_reference" >&2
              exit 1
            fi
            css_url="$css_reference"
            ;;
          http://*) css_url="$css_reference" ;;
          /*) css_url="${home_url%/}${css_reference}" ;;
        esac
        if result="$(curl --silent --show-error --max-time 10 --output /dev/null --write-out '%{http_code}|%{content_type}' "$css_url")"; then
          css_status="${result%%|*}"
          css_type="${result#*|}"
          if [[ "$css_status" == 200 && "${css_type,,}" == text/css* ]]; then
            echo "Healthy: /up, home page, and CSS asset ($css_url)"
            exit 0
          fi
          last_error="CSS asset returned HTTP $css_status with Content-Type $css_type: $css_url"
        else
          last_error="CSS asset could not be fetched: $css_url"
        fi
      else
        last_error="Home page has no Vite CSS reference under /build/assets/"
      fi
    else
      last_error="Home page returned HTTP ${status:-000}: $home_url"
    fi
  else
    last_error="Health endpoint returned HTTP ${status:-000}: $health_url"
  fi
  (( i < attempts )) && sleep 2
done

echo "Unhealthy after $attempts attempts: $last_error" >&2
exit 1
