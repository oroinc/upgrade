#!/usr/bin/env bash
# Step 1: Generate a list of changelog/upgrade .md files to download from GitHub.
# Reads composer-diff.json, queries GitHub compare API, filters to relevant files,
# outputs a TSV for review before downloading.
set -euo pipefail

DIFF_JSON=".ai-upgrade/composer-diff.json"
OUTPUT_TSV=".ai-upgrade/changelog-downloads.tsv"
ERRORS_LOG=".ai-upgrade/changelog-errors.log"

# Extract package list from JSON: name, repo (from link/compare URL), operation, base, target
# Skip removed packages and non-GitHub packages
jq -r '
  .packages | to_entries[] |
  select(.value.operation != "remove") |
  .value as $v |
  # Extract GitHub owner/repo from link or compare URL
  (($v.link // "") | capture("github\\.com/(?<r>[^/]+/[^/]+)") // null) as $from_link |
  (($v.compare // "") | capture("github\\.com/(?<r>[^/]+/[^/]+)") // null) as $from_compare |
  ($from_link // $from_compare) as $repo_match |
  select($repo_match != null) |
  # Clean versions (strip commit hash after space)
  (($v.version_base // "") | split(" ") | .[0]) as $base |
  (($v.version_target // "") | split(" ") | .[0]) as $target |
  [.key, $repo_match.r, $v.operation, $base, $target] | @tsv
' "$DIFF_JSON" > /tmp/changelog-packages.tsv

total=$(wc -l < /tmp/changelog-packages.tsv)
echo "Found $total GitHub-hosted packages" >&2

# Prepare output files
echo -e "# package\trepo\tref\tfilename" > "$OUTPUT_TSV"
echo "# Errors during compare API calls" > "$ERRORS_LOG"

downloaded=0
errors=0
skipped=0
n=0

while IFS=$'\t' read -r name repo op base target; do
  n=$((n + 1))

  # Skip new packages (no base) and dev versions
  if [ -z "$base" ] || [ "$op" = "install" ]; then
    echo "  [$n/$total] SKIP $name (new package)" >&2
    skipped=$((skipped + 1))
    continue
  fi
  if [[ "$base" == dev-* ]] || [[ "$target" == dev-* ]]; then
    echo "  [$n/$total] SKIP $name ($base → $target, dev)" >&2
    skipped=$((skipped + 1))
    continue
  fi
  if [ -z "$target" ]; then
    echo "  [$n/$total] SKIP $name (no target)" >&2
    skipped=$((skipped + 1))
    continue
  fi

  echo -n "  [$n/$total] $name ($base → $target)..." >&2

  # Call GitHub compare API, extract file count and .md/.changelog filenames
  # Try bare version first, then v-prefixed (most GitHub repos use v-prefixed tags)
  compare_jq='{count: (.files | length), md: [.files[] | select(.filename | test("\\.(md|MD)$|^CHANGELOG$|^UPGRADE$|^CHANGES$"; "i")) | .filename]}'

  compare_ok=false
  file_count=0
  md_files=""
  effective_target="$target"

  # Attempt 1: bare version refs
  api_response=$(gh api "repos/$repo/compare/$base...$target" --jq "$compare_jq" 2>/tmp/changelog-gh-err) || true
  if [ ! -s /tmp/changelog-gh-err ]; then
    compare_ok=true
  else
    # Attempt 2: v-prefixed refs
    : > /tmp/changelog-gh-err
    api_response=$(gh api "repos/$repo/compare/v$base...v$target" --jq "$compare_jq" 2>/tmp/changelog-gh-err) || true
    if [ ! -s /tmp/changelog-gh-err ]; then
      compare_ok=true
      effective_target="v$target"
    fi
  fi

  if [ "$compare_ok" = true ]; then
    file_count=$(echo "$api_response" | jq -r '.count // 0')
    md_files=$(echo "$api_response" | jq -r '.md[]' 2>/dev/null || true)
  else
    err=$(cat /tmp/changelog-gh-err)
    echo -e "$name\t$repo\t$base\t$target\t$err" >> "$ERRORS_LOG"
    errors=$((errors + 1))
    : > /tmp/changelog-gh-err
  fi

  # Filter to relevant files only (case-insensitive grep) — also matches CHANGELOG without extension
  relevant=""
  if [ -n "$md_files" ]; then
    relevant=$(echo "$md_files" | grep -iE \
      '^CHANGELOG[^/]*(\.(md|MD))?$|^UPGRADE[^/]*\.md$|^MIGRAT[^/]*\.md$|^BREAKING[^/]*\.md$|^CHANGES[^/]*\.md$|.*/migration/[^/]*\.md$' \
      || true)
  fi

  # Fallback: check well-known files directly when compare is unreliable
  need_fallback=false
  if [ "$compare_ok" = false ]; then need_fallback=true; fi
  if [ "$file_count" -ge 300 ] 2>/dev/null; then need_fallback=true; fi
  if [ -z "$relevant" ]; then need_fallback=true; fi

  if $need_fallback; then
    for known_file in CHANGELOG.md CHANGELOG UPGRADE.md CHANGES.md; do
      # Skip if already found
      if [ -n "$relevant" ] && echo "$relevant" | grep -qx "$known_file" 2>/dev/null; then
        continue
      fi
      # Try both bare and v-prefixed ref for contents API
      found_ref=""
      if gh api "repos/$repo/contents/$known_file?ref=$effective_target" --jq '.name' >/dev/null 2>&1; then
        found_ref="$effective_target"
      elif [ "$effective_target" = "$target" ] && gh api "repos/$repo/contents/$known_file?ref=v$target" --jq '.name' >/dev/null 2>&1; then
        found_ref="v$target"
        effective_target="v$target"
      fi
      if [ -n "$found_ref" ]; then
        if [ -n "$relevant" ]; then
          relevant="$relevant"$'\n'"$known_file"
        else
          relevant="$known_file"
        fi
      fi
    done
  fi

  if [ -z "$relevant" ]; then
    if [ "$compare_ok" = false ]; then
      echo " ERROR (no fallback files found)" >&2
    elif [ "$file_count" -ge 300 ]; then
      echo " truncated ($file_count files), no fallback" >&2
    else
      echo " no relevant (had: $(echo "$md_files" | tr '\n' ', '))" >&2
    fi
    continue
  fi

  count=0
  while IFS= read -r filename; do
    echo -e "$name\t$repo\t$effective_target\t$filename" >> "$OUTPUT_TSV"
    count=$((count + 1))
    downloaded=$((downloaded + 1))
  done <<< "$relevant"

  echo " $count file(s)" >&2

  # Gentle rate limiting
  sleep 0.05
done < /tmp/changelog-packages.tsv

echo "" >&2
echo "============================================================" >&2
echo "Files to download: $downloaded" >&2
echo "Errors: $errors" >&2
echo "Skipped: $skipped" >&2
echo "" >&2
echo "Review the list: $OUTPUT_TSV" >&2
echo "Errors log: $ERRORS_LOG" >&2
