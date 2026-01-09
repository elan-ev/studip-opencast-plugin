#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if ! command -v composer >/dev/null 2>&1; then
  echo "composer not found in PATH" >&2
  exit 1
fi

build_vendor_dir() {
  local composer_file="$1"
  local vendor_dir="$2"

  echo "Building ${vendor_dir} from ${composer_file}..."
  COMPOSER="${ROOT_DIR}/${composer_file}" \
  COMPOSER_VENDOR_DIR="${ROOT_DIR}/${vendor_dir}" \
    composer install \
      --no-dev \
      --prefer-dist \
      --no-interaction \
      --optimize-autoloader
}

build_vendor_dir "composer.studip5.json" "vendor-studip5"
build_vendor_dir "composer.studip6.json" "vendor-studip6"

echo "Done."
