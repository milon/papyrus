#!/usr/bin/env bash
set -euo pipefail

root="$(cd "$(dirname "$0")/.." && pwd)"
cd "$root"

mkdir -p build

# Production autoload only — keep build tools out of the PHAR.
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

BOX_PHAR="$root/build/box.phar"
if [[ ! -f "$BOX_PHAR" ]]; then
  curl -fsSL -o "$BOX_PHAR" \
    https://github.com/box-project/box/releases/download/4.7.0/box.phar
  chmod +x "$BOX_PHAR"
fi

# Optional: PAPYRUS_PHAR_VERSION=1.5.0 overrides the Box git-version placeholder.
app_file="$root/src/Console/Application.php"
restore_app=0
if [[ -n "${PAPYRUS_PHAR_VERSION:-}" ]]; then
  cp "$app_file" "$app_file.phar-bak"
  restore_app=1
  perl -pi -e 's/\@git_version\@/'"$PAPYRUS_PHAR_VERSION"'/g' "$app_file"
fi

cleanup() {
  if [[ "$restore_app" -eq 1 && -f "$app_file.phar-bak" ]]; then
    mv "$app_file.phar-bak" "$app_file"
  fi
}
trap cleanup EXIT

php -d phar.readonly=0 "$BOX_PHAR" compile -c box.json

echo "Built $root/build/papyrus.phar"
php build/papyrus.phar --version
ls -lh build/papyrus.phar
