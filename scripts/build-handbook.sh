#!/usr/bin/env bash
set -euo pipefail

root="$(cd "$(dirname "$0")/.." && pwd)"
cd "$root"

# mermaid-cli / Puppeteer: prefer system Chrome when the bundled headless shell is missing
if [[ -z "${PUPPETEER_EXECUTABLE_PATH:-}" ]]; then
  for candidate in \
    "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" \
    "/usr/bin/google-chrome" \
    "/usr/bin/chromium" \
    "/usr/bin/chromium-browser"
  do
    if [[ -x "$candidate" ]]; then
      export PUPPETEER_EXECUTABLE_PATH="$candidate"
      break
    fi
  done
fi

./bin/papyrus build:pdf -d examples/the-papyrus-handbook -e docs
./bin/papyrus build:html -d examples/the-papyrus-handbook -e docs
./bin/papyrus build:site -d examples/the-papyrus-handbook -e docs
