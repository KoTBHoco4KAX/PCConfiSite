#!/usr/bin/env bash

set -e

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$DIR"

cleanup() {
  echo ""
  echo "⛔ Останавливаю серверы..."
  kill "$LARAVEL_PID" 2>/dev/null || true
  kill "$VITE_PID"    2>/dev/null || true
  wait 2>/dev/null || true
  exit 0
}

trap cleanup INT TERM

echo "🚀 Laravel API → http://0.0.0.0:8000"
XDEBUG_MODE=off php artisan serve --host=0.0.0.0 --port=8000 &
LARAVEL_PID=$!

sleep 2

echo "🎨 Vite UI    → http://0.0.0.0:5173"
( cd frontend && npm run dev -- --host 0.0.0.0 --port 5173 ) &
VITE_PID=$!

echo ""
echo "════════════════════════════════════════════════════════"
echo "  ✅ API:  http://localhost:8000/api"
echo "  ✅ UI:   http://localhost:5173"
echo ""
echo "  👉 Codespaces: вкладка PORTS → порт 5173 → 🌐"
echo "  ⛔ Ctrl+C — остановить всё"
echo "════════════════════════════════════════════════════════"
echo ""

wait