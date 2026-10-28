#!/usr/bin/env bash
set -Eeuo pipefail
script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
repo_dir="$(cd -- "$script_dir/../.." && pwd)"
env_file="${PRODUCTION_ENV_FILE:-/opt/heatalert/config/production.env}"
backup_dir="${BACKUP_DIR:-/opt/heatalert/backups}"
[[ -f "$env_file" ]] || { echo "Missing private environment file" >&2; exit 1; }
[[ "$backup_dir" == /opt/heatalert/backups || "$backup_dir" == /opt/heatalert/backups/* ]] || { echo "BACKUP_DIR must be under /opt/heatalert/backups" >&2; exit 1; }
umask 077
mkdir -p -- "$backup_dir"
export PRODUCTION_ENV_FILE="$env_file"
compose=(docker compose --env-file "$env_file" -f "$repo_dir/deploy/compose.prod.yml")
stamp="$(date -u +%Y%m%dT%H%M%SZ)"
target="$backup_dir/heatalert-$stamp.sql.gz"
[[ ! -e "$target" ]] || { echo "Backup already exists" >&2; exit 1; }
tmp="$(mktemp "$backup_dir/.heatalert-XXXXXXXX.sql.gz")"
trap 'rm -f -- "$tmp"' EXIT
"${compose[@]}" exec -T mysql sh -c 'MYSQL_PWD="$MYSQL_PASSWORD" exec mysqldump --single-transaction --quick --no-tablespaces -u "$MYSQL_USER" "$MYSQL_DATABASE"' | gzip -1 > "$tmp"
gzip -t "$tmp"
[[ $(stat -c %s "$tmp") -gt 100 ]] || { echo "Backup is empty or suspiciously small" >&2; exit 1; }
mv -- "$tmp" "$target"
trap - EXIT
echo "Backup saved: $target"
