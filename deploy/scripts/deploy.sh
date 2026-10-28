#!/usr/bin/env bash
set -Eeuo pipefail
trap 'echo "Deployment failed at line $LINENO" >&2' ERR
script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
repo_dir="$(cd -- "$script_dir/../.." && pwd)"
env_file="${PRODUCTION_ENV_FILE:-/opt/heatalert/config/production.env}"
[[ -f "$env_file" ]] || { echo "Missing $env_file" >&2; exit 1; }
[[ "$env_file" == /opt/heatalert/config/production.env ]] || { echo "Use /opt/heatalert/config/production.env" >&2; exit 1; }
[[ "$env_file" != "$repo_dir"/* ]] || { echo "Private environment file cannot be in repository" >&2; exit 1; }
grep -qx 'APP_ENV=production' "$env_file" || { echo "APP_ENV must be production" >&2; exit 1; }
grep -qx 'APP_DEBUG=false' "$env_file" || { echo "APP_DEBUG must be false" >&2; exit 1; }
grep -Eq '^APP_KEY=base64:[A-Za-z0-9+/]{43}=$' "$env_file" || { echo "Generate and persist a valid APP_KEY" >&2; exit 1; }
grep -Eq '^APP_URL=http://[^[:space:]]+' "$env_file" || { echo "Set APP_URL for the private VM" >&2; exit 1; }
! grep -Eq '^(APP_URL|DB_PASSWORD|MYSQL_ROOT_PASSWORD)=.*(REPLACE|IP_DE_LA_VM)' "$env_file" || { echo "Replace all required placeholders" >&2; exit 1; }
[[ -n "${DOCKERHUB_NAMESPACE:-}" && "${DOCKERHUB_NAMESPACE}" =~ ^[a-z0-9][a-z0-9_-]*$ ]] || { echo "Invalid DOCKERHUB_NAMESPACE" >&2; exit 1; }
[[ "${IMAGE_TAG:-}" =~ ^[a-f0-9]{40}$ ]] || { echo "IMAGE_TAG must be a full Git SHA" >&2; exit 1; }
command -v docker >/dev/null
command -v curl >/dev/null
docker compose version >/dev/null
docker info >/dev/null
free_kb="$(df -Pk /var/lib/docker | awk 'NR==2 {print $4}')"
[[ "$free_kb" =~ ^[0-9]+$ && "$free_kb" -ge 2097152 ]] || { echo "At least 2 GiB free Docker disk space required" >&2; exit 1; }
export PRODUCTION_ENV_FILE="$env_file"
compose=(docker compose --env-file "$env_file" -f "$repo_dir/deploy/compose.prod.yml")
"${compose[@]}" config --quiet
"${compose[@]}" pull app nginx
"${compose[@]}" up -d mysql
for i in {1..30}; do
  container_id="$("${compose[@]}" ps -q mysql)"
  status="$(docker inspect --format '{{.State.Health.Status}}' "$container_id" 2>/dev/null || true)"
  [[ "$status" == healthy ]] && break
  sleep 2
done
[[ "$status" == healthy ]] || { echo "MySQL did not become healthy" >&2; exit 1; }
"$script_dir/backup-db.sh"
"${compose[@]}" up -d app
"${compose[@]}" exec -T app php artisan migrate --force
"${compose[@]}" exec -T app php artisan optimize
"${compose[@]}" up -d nginx
"$script_dir/healthcheck.sh"
echo "Deployment succeeded: ${IMAGE_TAG}"
