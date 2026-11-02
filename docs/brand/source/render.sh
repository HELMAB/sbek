#!/usr/bin/env bash
# Render the brand HTML sources to PNG with headless Chrome.
#   docs/brand/source/render.sh            → all three images
#   docs/brand/source/render.sh screenshot → just one
set -euo pipefail

here="$(cd "$(dirname "$0")" && pwd)"
theme="$(cd "$here/../../.." && pwd)"
chrome="${CHROME:-google-chrome}"

# name | width | height | scale | output (relative to the theme root)
jobs=(
	"screenshot|1200|900|1|screenshot.png"
	"options-banner|1200|240|2|assets/images/theme-options-banner.png"
	"readme-banner|1280|640|2|docs/brand/readme-banner.png"
)

for job in "${jobs[@]}"; do
	IFS='|' read -r name w h scale out <<<"$job"
	[[ $# -gt 0 && " $* " != *" $name "* ]] && continue
	mkdir -p "$(dirname "$theme/$out")"
	"$chrome" --headless=new --disable-gpu --hide-scrollbars --no-sandbox \
		--allow-file-access-from-files \
		--force-device-scale-factor="$scale" \
		--window-size="$w,$h" \
		--virtual-time-budget=8000 \
		--screenshot="$theme/$out" \
		"file://$here/$name.html" 2>/dev/null
	echo "✓ $out ($((w * scale))×$((h * scale)))"
done
