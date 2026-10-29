#!/usr/bin/env bash
set -Eeuo pipefail

: "${DOCKERHUB_NAMESPACE:?Set DOCKERHUB_NAMESPACE}"
: "${IMAGE_TAG:?Set IMAGE_TAG to the full tested Git commit SHA}"
[[ "$DOCKERHUB_NAMESPACE" =~ ^[a-z0-9][a-z0-9_-]*$ ]] || { echo "Invalid Docker Hub namespace" >&2; exit 1; }
[[ "$IMAGE_TAG" =~ ^[a-f0-9]{40}$ ]] || { echo "IMAGE_TAG must be a full 40-character Git SHA" >&2; exit 1; }

script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
source_dir="$(cd -- "$script_dir/../k8s" && pwd)"
umask 077
output_dir="$(mktemp -d "${TMPDIR:-/tmp}/heatalert-k8s.XXXXXXXX")"
for source_file in "$source_dir"/[0-9][0-9]-*.yaml; do
  sed \
    -e "s#aymen74407/heatalert-#${DOCKERHUB_NAMESPACE}/heatalert-#g" \
    -e "s#REPLACE_WITH_FULL_GIT_SHA#${IMAGE_TAG}#g" \
    "$source_file" > "$output_dir/$(basename -- "$source_file")"
done
if grep -R -q 'REPLACE_WITH_FULL_GIT_SHA' "$output_dir"; then
  echo "Unresolved image tag placeholder" >&2
  exit 1
fi
printf '%s\n' "$output_dir"
