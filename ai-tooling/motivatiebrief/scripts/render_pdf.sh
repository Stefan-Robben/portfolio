#!/usr/bin/env bash
# Render a letter HTML file to an A4 PDF with headless Chrome and check it is exactly one page.
# Usage: render_pdf.sh <input.html> <output.pdf>
set -euo pipefail

if [ $# -ne 2 ]; then
  echo "Gebruik: render_pdf.sh <brief.html> <uitvoer.pdf>" >&2
  exit 1
fi

in_dir="$(cd "$(dirname "$1")" && pwd)"
in="$in_dir/$(basename "$1")"
out="$2"

chrome="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
if [ ! -x "$chrome" ]; then
  chrome="$(command -v google-chrome || command -v chromium || true)"
fi
if [ -z "$chrome" ]; then
  echo "Google Chrome niet gevonden; installeer Chrome of pas het pad in dit script aan." >&2
  exit 1
fi

mkdir -p "$(dirname "$out")"
"$chrome" --headless=new --disable-gpu --no-pdf-header-footer --virtual-time-budget=3000 \
  --print-to-pdf="$out" "file://$in" 2>/dev/null

if command -v pdfinfo >/dev/null; then
  pages="$(pdfinfo "$out" | awk '/^Pages:/ {print $2}')"
else
  pages="$(mdls -raw -name kMDItemNumberOfPages "$out" 2>/dev/null || echo "?")"
fi

echo "PDF: $out ($pages pagina)"
if [ "$pages" != "1" ]; then
  echo "Let op: de brief beslaat $pages pagina's. Kort de tekst in tot hij op één A4 past." >&2
  exit 2
fi
