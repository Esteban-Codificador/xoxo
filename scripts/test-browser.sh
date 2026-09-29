#!/usr/bin/env bash
# Runs the E2E suite (tests/Browser, Pest Browser + Playwright).
#
#   scripts/test-browser.sh [pest arguments]
#
# Two workarounds live here so every caller (developers, CI, the cloud
# session) gets them:
#
# 1. Pest leaves its `playwright run-server` process running after it
#    exits. The orphan keeps stdout open, so anything reading the output
#    (a pipe, CI) would wait forever. It is stopped on exit.
# 2. When the machine ships a pre-installed Chromium of a different
#    revision than the Playwright package expects (the Claude Code cloud
#    image does, and cannot download browsers), a shim directory maps the
#    expected revision onto the installed one. With a normal
#    `npx playwright install` nothing changes.
set -uo pipefail

cd "$(dirname "$0")/.."

cleanup() {
    pkill -f "[p]laywright run-server" 2>/dev/null || return 0
    # It does not always honour SIGTERM right after a run: give it 3 s.
    for _ in 1 2 3 4 5 6; do
        pgrep -f "[p]laywright run-server" >/dev/null || return 0
        sleep 0.5
    done
    pkill -KILL -f "[p]laywright run-server" 2>/dev/null || true
}
trap cleanup EXIT

browsers="${PLAYWRIGHT_BROWSERS_PATH:-}"
expected=$(node -e "const b=require('./node_modules/playwright-core/browsers.json').browsers;console.log(b.find(x=>x.name==='chromium-headless-shell').revision)" 2>/dev/null || true)

if [ -n "$browsers" ] && [ -n "$expected" ] && [ ! -d "$browsers/chromium_headless_shell-$expected" ]; then
    installed=$(find "$browsers" -maxdepth 1 -type d -name 'chromium_headless_shell-*' | sort | tail -n 1)

    if [ -n "$installed" ] && [ -x "$installed/chrome-linux/headless_shell" ]; then
        shim="${TMPDIR:-/tmp}/playwright-browsers-shim"
        target="$shim/chromium_headless_shell-$expected/chrome-headless-shell-linux64"
        mkdir -p "$target"
        for file in "$installed"/chrome-linux/*; do
            ln -sfn "$file" "$target/$(basename "$file")"
        done
        ln -sfn "$installed/chrome-linux/headless_shell" "$target/chrome-headless-shell"
        touch "$shim/chromium_headless_shell-$expected/INSTALLATION_COMPLETE"
        export PLAYWRIGHT_BROWSERS_PATH="$shim"
        echo "Using $(basename "$installed") as chromium_headless_shell-$expected (${shim})"
    fi
fi

./vendor/bin/pest --testsuite=Browser "$@"
