#!/usr/bin/env bash
set +x
set -Eeuo pipefail

[[ "${K8S_SECRETS_CONFIRM:-}" == 1 ]] || { echo "Set K8S_SECRETS_CONFIRM=1 for this explicit secret operation" >&2; exit 1; }
: "${K8S_CONTEXT:?Set the intended kubectl context explicitly}"
: "${K8S_APP_URL:?Set the Kubernetes application URL explicitly}"
[[ "$K8S_APP_URL" =~ ^https?://[^[:space:]]+$ ]] || { echo "Invalid K8S_APP_URL" >&2; exit 1; }
env_file="${PRODUCTION_ENV_FILE:-/opt/heatalert/config/production.env}"
[[ "$env_file" == /opt/heatalert/config/production.env && -f "$env_file" ]] || { echo "Private production.env missing at the expected VM path" >&2; exit 1; }
command -v kubectl >/dev/null
umask 077
tmp_dir="$(mktemp -d "${TMPDIR:-/tmp}/heatalert-k8s-secrets.XXXXXXXX")"
trap 'rm -rf -- "$tmp_dir"' EXIT
mkdir -- "$tmp_dir/app" "$tmp_dir/mysql"
declare -A values=()
while IFS= read -r line || [[ -n "$line" ]]; do
  line="${line%$'\r'}"
  [[ -z "$line" || "$line" == \#* ]] && continue
  [[ "$line" =~ ^([A-Za-z_][A-Za-z0-9_]*)=(.*)$ ]] || { echo "Invalid private env file format" >&2; exit 1; }
  key="${BASH_REMATCH[1]}"
  value="${BASH_REMATCH[2]}"
  [[ -z "${values[$key]+x}" ]] || { echo "Duplicate key in private env file" >&2; exit 1; }
  if [[ "$value" == \"*\" || "$value" == \'*\' ]]; then
    value="${value:1:${#value}-2}"
  fi
  values[$key]="$value"
done < "$env_file"

[[ "${values[APP_ENV]:-}" == production && "${values[APP_DEBUG]:-}" == false ]] || { echo "Production app settings are required" >&2; exit 1; }
[[ "${values[APP_KEY]:-}" =~ ^base64:[A-Za-z0-9+/]{43}=$ ]] || { echo "Existing APP_KEY is missing or invalid" >&2; exit 1; }
[[ "${values[DB_DATABASE]:-}" == heatalert && "${values[DB_USERNAME]:-}" != root && -n "${values[DB_USERNAME]:-}" ]] || { echo "Invalid application database account" >&2; exit 1; }
[[ -n "${values[DB_PASSWORD]:-}" && -n "${values[MYSQL_ROOT_PASSWORD]:-}" ]] || { echo "Database credentials are missing" >&2; exit 1; }
[[ "${values[SESSION_DRIVER]:-}" == database && "${values[QUEUE_CONNECTION]:-}" == sync ]] || { echo "Unsupported session or queue configuration" >&2; exit 1; }
[[ "${values[CACHE_STORE]:-}" == file ]] || { echo "CACHE_STORE must be file" >&2; exit 1; }
[[ "${values[APP_KEY]}" != *REPLACE* && "${values[DB_PASSWORD]}" != *REPLACE* && "${values[MYSQL_ROOT_PASSWORD]}" != *REPLACE* ]] || { echo "Replace placeholders in the private env file" >&2; exit 1; }

values[APP_URL]="$K8S_APP_URL"
values[DB_HOST]=mysql
values[DB_PORT]=3306
values[DB_CONNECTION]=mysql
for key in "${!values[@]}"; do
  case "$key" in
    MYSQL_ROOT_PASSWORD|HTTP_PORT|DOCKERHUB_NAMESPACE|IMAGE_TAG|FORWARD_*) continue ;;
  esac
  printf %s "${values[$key]}" > "$tmp_dir/app/$key"
done
for key in DB_USERNAME DB_PASSWORD MYSQL_ROOT_PASSWORD; do
  printf %s "${values[$key]}" > "$tmp_dir/mysql/$key"
done

kubectl --context "$K8S_CONTEXT" get namespace heatalert >/dev/null
kubectl --context "$K8S_CONTEXT" get node heatalert-single-node >/dev/null
app_args=()
for key_file in "$tmp_dir/app"/*; do
  app_args+=("--from-file=$(basename -- "$key_file")=$key_file")
done
mysql_args=()
for key_file in "$tmp_dir/mysql"/*; do
  mysql_args+=("--from-file=$(basename -- "$key_file")=$key_file")
done
kubectl --context "$K8S_CONTEXT" -n heatalert create secret generic heatalert-app-env "${app_args[@]}" --dry-run=client -o yaml \
  | kubectl --context "$K8S_CONTEXT" -n heatalert apply --server-side --field-manager=heatalert-secrets -f - >/dev/null
kubectl --context "$K8S_CONTEXT" -n heatalert create secret generic heatalert-mysql "${mysql_args[@]}" --dry-run=client -o yaml \
  | kubectl --context "$K8S_CONTEXT" -n heatalert apply --server-side --field-manager=heatalert-secrets -f - >/dev/null
echo "Kubernetes Secrets created or updated in namespace heatalert"
