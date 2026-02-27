#!/usr/bin/env bash
# Step 2: Download changelog/upgrade .md files listed in changelog-downloads.tsv.
# Uses gh api with raw content Accept header to download files.
# Places them in changelogs/ following vendor folder structure.
#
# Usage: scripts/download-changelogs.sh [--skip-existing]
set -euo pipefail

INPUT_TSV=".ai-upgrade/changelog-downloads.tsv"
OUTPUT_DIR="changelogs"

SKIP_EXISTING=false
if [[ "${1:-}" == "--skip-existing" ]]; then
  SKIP_EXISTING=true
fi

if [ ! -f "$INPUT_TSV" ]; then
  echo "ERROR: $INPUT_TSV not found. Run generate-changelog-list.sh first." >&2
  exit 1
fi

if [ "$SKIP_EXISTING" = false ]; then
  # Clean output directory
  rm -rf "$OUTPUT_DIR"
fi
mkdir -p "$OUTPUT_DIR"

total=$(grep -cv '^#' "$INPUT_TSV" | tr -d ' ')
n=0
ok=0
fail=0
skipped_existing=0

while IFS=$'\t' read -r package repo ref filename; do
  # Skip header/comments
  [[ "$package" == \#* ]] && continue
  [ -z "$package" ] && continue

  n=$((n + 1))

  # Build output path: changelogs/<package>/<filename>
  outdir="$OUTPUT_DIR/$package"
  # Handle nested paths (e.g., docs/book/v4/migration/v3-to-v4.md)
  filedir=$(dirname "$filename")
  if [ "$filedir" != "." ]; then
    outdir="$outdir/$filedir"
  fi
  mkdir -p "$outdir"
  outfile="$OUTPUT_DIR/$package/$filename"

  # Skip if file already exists (when --skip-existing)
  if [ "$SKIP_EXISTING" = true ] && [ -f "$outfile" ] && [ -s "$outfile" ]; then
    echo "  [$n/$total] $package/$filename... SKIP (exists)" >&2
    skipped_existing=$((skipped_existing + 1))
    continue
  fi

  echo -n "  [$n/$total] $package/$filename..." >&2

  # Download using gh api with raw content header
  if gh api "repos/$repo/contents/$filename?ref=$ref" \
    -H "Accept: application/vnd.github.raw+json" \
    > "$outfile" 2>/tmp/dl-changelog-err; then
    size=$(wc -c < "$outfile" | tr -d ' ')
    echo " ${size}B" >&2
    ok=$((ok + 1))
  else
    err=$(cat /tmp/dl-changelog-err)
    echo " FAILED: $err" >&2
    rm -f "$outfile"
    fail=$((fail + 1))
  fi

  sleep 0.05
done < "$INPUT_TSV"

echo "" >&2
echo "============================================================" >&2
echo "Downloaded: $ok" >&2
echo "Failed: $fail" >&2
if [ "$SKIP_EXISTING" = true ]; then
  echo "Skipped (existing): $skipped_existing" >&2
fi
echo "Output: $OUTPUT_DIR/" >&2
echo "" >&2

# Show tree
if command -v tree &>/dev/null; then
  tree "$OUTPUT_DIR" --dirsfirst -I '__pycache__'
fi
