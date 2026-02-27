#!/usr/bin/env bash
# Trim downloaded changelog files to keep only the relevant version range.
# Reads version ranges from .ai-upgrade/composer-diff.json and file list from
# .ai-upgrade/changelog-downloads.tsv, then trims each file in-place.
#
# Usage: trim-changelogs.sh [--dry-run]
# Run from workspace root.

set -uo pipefail

CHANGELOGS_DIR="changelogs"
TSV_FILE=".ai-upgrade/changelog-downloads.tsv"
DIFF_FILE=".ai-upgrade/composer-diff.json"

DRY_RUN=false
if [[ "${1:-}" == "--dry-run" ]]; then
    DRY_RUN=true
fi

# Result codes (set via TRIM_RESULT variable, not return codes)
RESULT_TRIMMED=0
RESULT_KEPT=1
RESULT_EMPTIED=2
# shellcheck disable=SC2034
RESULT_SKIPPED=3

# Counters
TOTAL=0
TRIMMED=0
SKIPPED=0
EMPTIED=0
KEPT_AS_IS=0

# ── Helpers ──────────────────────────────────────────────────────────────────

# Normalize version: strip leading 'v', pad X.Y to X.Y.0
normalize_version() {
    local v="$1"
    v="${v#v}"
    if [[ "$v" =~ ^[0-9]+\.[0-9]+$ ]]; then
        v="$v.0"
    fi
    echo "$v"
}

# Returns 0 (true) if $1 <= $2
version_le() {
    [[ "$(printf '%s\n' "$1" "$2" | sort -V | head -1)" == "$1" ]]
}

# Get base version for a package from composer-diff.json
get_base_version() {
    local pkg="$1"
    jq -r --arg p "$pkg" '.packages[$p].version_base // empty' "$DIFF_FILE"
}

# Check if preamble before cut_line has meaningful content (UNRELEASED sections, etc.)
# Returns 0 (true) if preamble has non-blank, non-TOC content
has_meaningful_preamble() {
    local file="$1" cut_line="$2"
    local preamble_end=$((cut_line - 1))
    # Strip blank lines, TOC lines (- [...]), and heading-only lines
    local content
    content=$(head -n "$preamble_end" "$file" | grep -cvE '^\s*$|^- \[|^#+ |^=+$|^-+$' || true)
    [[ "$content" -gt 0 ]]
}

# ── Format: Symfony component changelogs ─────────────────────────────────────
# Headers: bare `X.Y` on its own line, followed by `---`
trim_symfony() {
    local file="$1" base_norm="$2"

    # Find all version header lines (line matching ^X.Y$ where next line is ---)
    local -a header_lines=()
    local -a header_versions=()
    local total_lines
    total_lines=$(wc -l < "$file")

    while IFS= read -r match; do
        local lnum="${match%%:*}"
        local ver="${match#*:}"
        ver="$(echo "$ver" | tr -d '[:space:]')"
        header_lines+=("$lnum")
        header_versions+=("$ver")
    done < <(grep -n '^[0-9]\{1,\}\.[0-9]\{1,\}$' "$file" | while IFS=: read -r ln content; do
        local next_line
        next_line=$(sed -n "$((ln+1))p" "$file")
        if [[ "$next_line" =~ ^---+ ]]; then
            echo "$ln:$content"
        fi
    done)

    if [[ ${#header_lines[@]} -eq 0 ]]; then
        echo "  No version headers found, keeping as-is"
        TRIM_RESULT=$RESULT_KEPT
        return
    fi

    local cut_line=""
    local keep_count=0
    local remove_count=0
    local -a keeping=()
    local -a removing=()

    for i in "${!header_versions[@]}"; do
        local hv="${header_versions[$i]}"
        local hv_norm
        hv_norm=$(normalize_version "$hv")
        if version_le "$hv_norm" "$base_norm"; then
            cut_line="${header_lines[$i]}"
            remove_count=$(( ${#header_versions[@]} - i ))
            removing=("${header_versions[@]:$i}")
            break
        fi
        keep_count=$((keep_count + 1))
        keeping+=("$hv")
    done

    if [[ -z "$cut_line" ]]; then
        echo "  All ${#header_versions[@]} versions are relevant, keeping as-is"
        TRIM_RESULT=$RESULT_KEPT
        return
    fi

    if [[ $keep_count -eq 0 ]]; then
        if has_meaningful_preamble "$file" "$cut_line"; then
            echo "  All versions at or below base, but preamble has content (UNRELEASED?) — keeping preamble"
            if [[ "$DRY_RUN" == false ]]; then
                local keep_end=$((cut_line - 1))
                while [[ $keep_end -gt 0 ]] && [[ -z "$(sed -n "${keep_end}p" "$file" | tr -d '[:space:]')" ]]; do
                    keep_end=$((keep_end - 1))
                done
                head -n "$keep_end" "$file" > "$file.tmp" && mv "$file.tmp" "$file"
            fi
            TRIM_RESULT=$RESULT_TRIMMED
            return
        fi
        echo "  All ${#header_versions[@]} versions at or below base $base_norm — file becomes empty"
        if [[ "$DRY_RUN" == false ]]; then
            : > "$file"
        fi
        TRIM_RESULT=$RESULT_EMPTIED
        return
    fi

    echo "  Versions found: ${header_versions[*]}"
    echo "  Keeping: ${keeping[*]} ($keep_count versions, lines 1-$((cut_line - 1)))"
    echo "  Removing: ${removing[*]} ($remove_count versions, lines $cut_line-$total_lines)"

    if [[ "$DRY_RUN" == false ]]; then
        local keep_end=$((cut_line - 1))
        while [[ $keep_end -gt 0 ]] && [[ -z "$(sed -n "${keep_end}p" "$file" | tr -d '[:space:]')" ]]; do
            keep_end=$((keep_end - 1))
        done
        head -n "$keep_end" "$file" > "$file.tmp" && mv "$file.tmp" "$file"
    fi
    TRIM_RESULT=$RESULT_TRIMMED
}

# ── Format: Markdown ## headers ──────────────────────────────────────────────
# Covers: ## X.Y.Z, ## [X.Y.Z], ## X.Y.Z - date, ## [X.Y.Z] - date,
#          ## X.Y.Z (date), ## [X.Y.Z](url)
# Also handles TOC lines like `- [X.Y.Z](#...)` (Oro style)
trim_h2() {
    local file="$1" base_norm="$2"
    local total_lines
    total_lines=$(wc -l < "$file")

    local -a header_lines=()
    local -a header_versions=()

    while IFS= read -r match; do
        local lnum="${match%%:*}"
        local content="${match#*:}"
        local ver
        ver=$(echo "$content" | sed -E 's/^## *\[?v?([0-9]+\.[0-9]+(\.[0-9]+)?(-[a-zA-Z0-9.]+)?)\]?.*/\1/')
        if [[ -n "$ver" && "$ver" != "$content" ]]; then
            header_lines+=("$lnum")
            header_versions+=("$ver")
        fi
    done < <(grep -n '^## *\[*v*[0-9]' "$file")

    if [[ ${#header_lines[@]} -eq 0 ]]; then
        echo "  No ## version headers found, keeping as-is"
        TRIM_RESULT=$RESULT_KEPT
        return
    fi

    local cut_line=""
    local keep_count=0
    local remove_count=0
    local -a keeping=()
    local -a removing=()

    for i in "${!header_versions[@]}"; do
        local hv="${header_versions[$i]}"
        local hv_norm
        hv_norm=$(normalize_version "$hv")
        if version_le "$hv_norm" "$base_norm"; then
            cut_line="${header_lines[$i]}"
            remove_count=$(( ${#header_versions[@]} - i ))
            removing=("${header_versions[@]:$i}")
            break
        fi
        keep_count=$((keep_count + 1))
        keeping+=("$hv")
    done

    if [[ -z "$cut_line" ]]; then
        echo "  All ${#header_versions[@]} versions are relevant, keeping as-is"
        TRIM_RESULT=$RESULT_KEPT
        return
    fi

    if [[ $keep_count -eq 0 ]]; then
        if has_meaningful_preamble "$file" "$cut_line"; then
            echo "  All versions at or below base, but preamble has content (UNRELEASED?) — keeping preamble"
            if [[ "$DRY_RUN" == false ]]; then
                local keep_end=$((cut_line - 1))
                while [[ $keep_end -gt 0 ]] && [[ -z "$(sed -n "${keep_end}p" "$file" | tr -d '[:space:]')" ]]; do
                    keep_end=$((keep_end - 1))
                done
                head -n "$keep_end" "$file" > "$file.tmp" && mv "$file.tmp" "$file"
            fi
            TRIM_RESULT=$RESULT_TRIMMED
            return
        fi
        echo "  All ${#header_versions[@]} versions at or below base $base_norm — file becomes empty"
        if [[ "$DRY_RUN" == false ]]; then
            : > "$file"
        fi
        TRIM_RESULT=$RESULT_EMPTIED
        return
    fi

    echo "  Versions found: ${header_versions[*]}"
    echo "  Keeping: ${keeping[*]} ($keep_count versions, lines 1-$((cut_line - 1)))"
    echo "  Removing: ${removing[*]} ($remove_count versions, lines $cut_line-$total_lines)"

    if [[ "$DRY_RUN" == false ]]; then
        local keep_end=$((cut_line - 1))
        while [[ $keep_end -gt 0 ]] && [[ -z "$(sed -n "${keep_end}p" "$file" | tr -d '[:space:]')" ]]; do
            keep_end=$((keep_end - 1))
        done

        local kept
        kept=$(head -n "$keep_end" "$file")

        # Also trim TOC entries for removed versions (Oro style: `- [X.Y.Z](#...`)
        local toc_trimmed="$kept"
        for rv in "${removing[@]}"; do
            toc_trimmed=$(echo "$toc_trimmed" | grep -v "^- \[${rv}" || true)
        done

        echo "$toc_trimmed" > "$file"
    fi
    TRIM_RESULT=$RESULT_TRIMMED
}

# ── Format: Markdown ### headers ─────────────────────────────────────────────
# Covers: ### X.Y.Z, ### X.Y.Z (date), ### [X.Y.Z] - date
# Used by: monolog CHANGELOG, imagine, kreait, monolog UPGRADE
trim_h3() {
    local file="$1" base_norm="$2"
    local total_lines
    total_lines=$(wc -l < "$file")

    local -a header_lines=()
    local -a header_versions=()

    while IFS= read -r match; do
        local lnum="${match%%:*}"
        local content="${match#*:}"
        local ver
        ver=$(echo "$content" | sed -E 's/^### *\[?v?([0-9]+\.[0-9]+(\.[0-9]+)?(-[a-zA-Z0-9.]+)?)\]?.*/\1/')
        if [[ -n "$ver" && "$ver" != "$content" ]]; then
            header_lines+=("$lnum")
            header_versions+=("$ver")
        fi
    done < <(grep -n '^### *\[*v*[0-9]' "$file")

    if [[ ${#header_lines[@]} -eq 0 ]]; then
        echo "  No ### version headers found, keeping as-is"
        TRIM_RESULT=$RESULT_KEPT
        return
    fi

    local cut_line=""
    local keep_count=0
    local remove_count=0
    local -a keeping=()
    local -a removing=()

    for i in "${!header_versions[@]}"; do
        local hv="${header_versions[$i]}"
        local hv_norm
        hv_norm=$(normalize_version "$hv")
        if version_le "$hv_norm" "$base_norm"; then
            cut_line="${header_lines[$i]}"
            remove_count=$(( ${#header_versions[@]} - i ))
            removing=("${header_versions[@]:$i}")
            break
        fi
        keep_count=$((keep_count + 1))
        keeping+=("$hv")
    done

    if [[ -z "$cut_line" ]]; then
        echo "  All ${#header_versions[@]} versions are relevant, keeping as-is"
        TRIM_RESULT=$RESULT_KEPT
        return
    fi

    if [[ $keep_count -eq 0 ]]; then
        if has_meaningful_preamble "$file" "$cut_line"; then
            echo "  All versions at or below base, but preamble has content (UNRELEASED?) — keeping preamble"
            if [[ "$DRY_RUN" == false ]]; then
                local keep_end=$((cut_line - 1))
                while [[ $keep_end -gt 0 ]] && [[ -z "$(sed -n "${keep_end}p" "$file" | tr -d '[:space:]')" ]]; do
                    keep_end=$((keep_end - 1))
                done
                head -n "$keep_end" "$file" > "$file.tmp" && mv "$file.tmp" "$file"
            fi
            TRIM_RESULT=$RESULT_TRIMMED
            return
        fi
        echo "  All ${#header_versions[@]} versions at or below base $base_norm — file becomes empty"
        if [[ "$DRY_RUN" == false ]]; then
            : > "$file"
        fi
        TRIM_RESULT=$RESULT_EMPTIED
        return
    fi

    echo "  Versions found: ${header_versions[*]}"
    echo "  Keeping: ${keeping[*]} ($keep_count versions, lines 1-$((cut_line - 1)))"
    echo "  Removing: ${removing[*]} ($remove_count versions, lines $cut_line-$total_lines)"

    if [[ "$DRY_RUN" == false ]]; then
        local keep_end=$((cut_line - 1))
        while [[ $keep_end -gt 0 ]] && [[ -z "$(sed -n "${keep_end}p" "$file" | tr -d '[:space:]')" ]]; do
            keep_end=$((keep_end - 1))
        done
        head -n "$keep_end" "$file" > "$file.tmp" && mv "$file.tmp" "$file"
    fi
    TRIM_RESULT=$RESULT_TRIMMED
}

# ── Format: Single # headers (Twig style: `# X.Y.Z (date)`) ─────────────────
# Matches: # X.Y.Z, # X.Y.Z (date)
trim_h1() {
    local file="$1" base_norm="$2"
    local total_lines
    total_lines=$(wc -l < "$file")

    local -a header_lines=()
    local -a header_versions=()

    while IFS= read -r match; do
        local lnum="${match%%:*}"
        local content="${match#*:}"
        local ver
        ver=$(echo "$content" | sed -E 's/^# *v?([0-9]+\.[0-9]+(\.[0-9]+)?(-[a-zA-Z0-9.]+)?).*/\1/')
        if [[ -n "$ver" && "$ver" != "$content" ]]; then
            header_lines+=("$lnum")
            header_versions+=("$ver")
        fi
    done < <(grep -n '^# *v*[0-9]' "$file" | grep -v '^[0-9]*:# *Upgrade to ')

    if [[ ${#header_lines[@]} -eq 0 ]]; then
        echo "  No # version headers found, keeping as-is"
        TRIM_RESULT=$RESULT_KEPT
        return
    fi

    local cut_line=""
    local keep_count=0
    local remove_count=0
    local -a keeping=()
    local -a removing=()

    for i in "${!header_versions[@]}"; do
        local hv="${header_versions[$i]}"
        local hv_norm
        hv_norm=$(normalize_version "$hv")
        if version_le "$hv_norm" "$base_norm"; then
            cut_line="${header_lines[$i]}"
            remove_count=$(( ${#header_versions[@]} - i ))
            removing=("${header_versions[@]:$i}")
            break
        fi
        keep_count=$((keep_count + 1))
        keeping+=("$hv")
    done

    if [[ -z "$cut_line" ]]; then
        echo "  All ${#header_versions[@]} versions are relevant, keeping as-is"
        TRIM_RESULT=$RESULT_KEPT
        return
    fi

    if [[ $keep_count -eq 0 ]]; then
        if has_meaningful_preamble "$file" "$cut_line"; then
            echo "  All versions at or below base, but preamble has content (UNRELEASED?) — keeping preamble"
            if [[ "$DRY_RUN" == false ]]; then
                local keep_end=$((cut_line - 1))
                while [[ $keep_end -gt 0 ]] && [[ -z "$(sed -n "${keep_end}p" "$file" | tr -d '[:space:]')" ]]; do
                    keep_end=$((keep_end - 1))
                done
                head -n "$keep_end" "$file" > "$file.tmp" && mv "$file.tmp" "$file"
            fi
            TRIM_RESULT=$RESULT_TRIMMED
            return
        fi
        echo "  All ${#header_versions[@]} versions at or below base $base_norm — file becomes empty"
        if [[ "$DRY_RUN" == false ]]; then
            : > "$file"
        fi
        TRIM_RESULT=$RESULT_EMPTIED
        return
    fi

    echo "  Versions found: ${header_versions[*]}"
    echo "  Keeping: ${keeping[*]} ($keep_count versions, lines 1-$((cut_line - 1)))"
    echo "  Removing: ${removing[*]} ($remove_count versions, lines $cut_line-$total_lines)"

    if [[ "$DRY_RUN" == false ]]; then
        local keep_end=$((cut_line - 1))
        while [[ $keep_end -gt 0 ]] && [[ -z "$(sed -n "${keep_end}p" "$file" | tr -d '[:space:]')" ]]; do
            keep_end=$((keep_end - 1))
        done
        head -n "$keep_end" "$file" > "$file.tmp" && mv "$file.tmp" "$file"
    fi
    TRIM_RESULT=$RESULT_TRIMMED
}

# ── Format: Doctrine UPGRADE style (`# Upgrade to X.Y[.Z]`) ─────────────────
trim_doctrine_upgrade() {
    local file="$1" base_norm="$2"
    local total_lines
    total_lines=$(wc -l < "$file")

    local -a header_lines=()
    local -a header_versions=()

    while IFS= read -r match; do
        local lnum="${match%%:*}"
        local content="${match#*:}"
        local ver
        ver=$(echo "$content" | sed -E 's/^# Upgrade to v?([0-9]+\.[0-9]+(\.[0-9]+)?).*/\1/')
        if [[ -n "$ver" && "$ver" != "$content" ]]; then
            header_lines+=("$lnum")
            header_versions+=("$ver")
        fi
    done < <(grep -n '^# Upgrade to ' "$file")

    if [[ ${#header_lines[@]} -eq 0 ]]; then
        echo "  No '# Upgrade to' headers found, keeping as-is"
        TRIM_RESULT=$RESULT_KEPT
        return
    fi

    local cut_line=""
    local keep_count=0
    local remove_count=0
    local -a keeping=()
    local -a removing=()

    for i in "${!header_versions[@]}"; do
        local hv="${header_versions[$i]}"
        local hv_norm
        hv_norm=$(normalize_version "$hv")
        if version_le "$hv_norm" "$base_norm"; then
            cut_line="${header_lines[$i]}"
            remove_count=$(( ${#header_versions[@]} - i ))
            removing=("${header_versions[@]:$i}")
            break
        fi
        keep_count=$((keep_count + 1))
        keeping+=("$hv")
    done

    if [[ -z "$cut_line" ]]; then
        echo "  All ${#header_versions[@]} versions are relevant, keeping as-is"
        TRIM_RESULT=$RESULT_KEPT
        return
    fi

    if [[ $keep_count -eq 0 ]]; then
        if has_meaningful_preamble "$file" "$cut_line"; then
            echo "  All versions at or below base, but preamble has content (UNRELEASED?) — keeping preamble"
            if [[ "$DRY_RUN" == false ]]; then
                local keep_end=$((cut_line - 1))
                while [[ $keep_end -gt 0 ]] && [[ -z "$(sed -n "${keep_end}p" "$file" | tr -d '[:space:]')" ]]; do
                    keep_end=$((keep_end - 1))
                done
                head -n "$keep_end" "$file" > "$file.tmp" && mv "$file.tmp" "$file"
            fi
            TRIM_RESULT=$RESULT_TRIMMED
            return
        fi
        echo "  All ${#header_versions[@]} versions at or below base $base_norm — file becomes empty"
        if [[ "$DRY_RUN" == false ]]; then
            : > "$file"
        fi
        TRIM_RESULT=$RESULT_EMPTIED
        return
    fi

    echo "  Versions found: ${header_versions[*]}"
    echo "  Keeping: ${keeping[*]} ($keep_count versions, lines 1-$((cut_line - 1)))"
    echo "  Removing: ${removing[*]} ($remove_count versions, lines $cut_line-$total_lines)"

    if [[ "$DRY_RUN" == false ]]; then
        local keep_end=$((cut_line - 1))
        while [[ $keep_end -gt 0 ]] && [[ -z "$(sed -n "${keep_end}p" "$file" | tr -d '[:space:]')" ]]; do
            keep_end=$((keep_end - 1))
        done
        head -n "$keep_end" "$file" > "$file.tmp" && mv "$file.tmp" "$file"
    fi
    TRIM_RESULT=$RESULT_TRIMMED
}

# ── Format: single-version UPGRADE-X.Y.md files ─────────────────────────────
# Version is in the filename. Either entirely relevant or entirely irrelevant.
trim_versioned_file() {
    local file="$1" base_norm="$2"
    local basename
    basename=$(basename "$file")

    local ver
    ver=$(echo "$basename" | sed -E 's/^UPGRADE-v?([0-9]+\.[0-9]+(\.[0-9]+)?).*/\1/')
    if [[ -z "$ver" || "$ver" == "$basename" ]]; then
        echo "  Cannot extract version from filename, keeping as-is"
        TRIM_RESULT=$RESULT_KEPT
        return
    fi

    local ver_norm
    ver_norm=$(normalize_version "$ver")

    if version_le "$ver_norm" "$base_norm"; then
        echo "  Version $ver <= base — entire file irrelevant, emptying"
        if [[ "$DRY_RUN" == false ]]; then
            : > "$file"
        fi
        TRIM_RESULT=$RESULT_EMPTIED
    else
        echo "  Version $ver > base — entire file relevant, keeping as-is"
        TRIM_RESULT=$RESULT_KEPT
    fi
}

# ── Detect format and dispatch ───────────────────────────────────────────────
detect_and_trim() {
    local file="$1" base_norm="$2" pkg="$3" filename="$4"
    TRIM_RESULT=$RESULT_KEPT

    # Special case: laminas migration docs — always keep entirely
    if [[ "$file" == */docs/book/* ]]; then
        echo "  Migration doc — keeping entirely"
        return
    fi

    # Special case: individual UPGRADE-X.Y.md files (DoctrineBundle, mongodb)
    if [[ "$filename" =~ ^UPGRADE-[0-9] ]]; then
        trim_versioned_file "$file" "$base_norm"
        return
    fi

    # Detect format by scanning file content
    local first_50
    first_50=$(head -50 "$file")

    # Check for Doctrine-style `# Upgrade to X.Y`
    if echo "$first_50" | grep -q '^# Upgrade to '; then
        trim_doctrine_upgrade "$file" "$base_norm"
        return
    fi

    # Check for single-# version headers (Twig style: # X.Y.Z or # X.Y.Z (date))
    # Must come after Doctrine "# Upgrade to" check
    if echo "$first_50" | grep -qE '^# *v?[0-9]+\.[0-9]+'; then
        if ! echo "$first_50" | grep -q '^# Upgrade to '; then
            trim_h1 "$file" "$base_norm"
            return
        fi
    fi

    # Check for Symfony-style bare version headers (X.Y\n---)
    if echo "$first_50" | grep -qE '^[0-9]+\.[0-9]+$'; then
        local bare_ver_line
        bare_ver_line=$(grep -n -E '^[0-9]+\.[0-9]+$' "$file" | head -1 | cut -d: -f1)
        if [[ -n "$bare_ver_line" ]]; then
            local next
            next=$(sed -n "$((bare_ver_line + 1))p" "$file")
            if [[ "$next" =~ ^---+ ]]; then
                trim_symfony "$file" "$base_norm"
                return
            fi
        fi
    fi

    # Check for ### X.Y.Z headers — only use if no h2 version headers exist
    if grep -qE '^### *\[?v?[0-9]+\.[0-9]+' "$file"; then
        if ! grep -qE '^## *\[?v?[0-9]+\.[0-9]+' "$file"; then
            trim_h3 "$file" "$base_norm"
            return
        fi
    fi

    # Check for ## X.Y.Z or ## [X.Y.Z] headers (most common)
    if grep -qE '^## *\[?v?[0-9]+\.[0-9]+' "$file"; then
        trim_h2 "$file" "$base_norm"
        return
    fi

    # Fallback: ### headers
    if grep -qE '^### *\[?v?[0-9]+\.[0-9]+' "$file"; then
        trim_h3 "$file" "$base_norm"
        return
    fi

    echo "  Unknown format, keeping as-is"
}

# ── Main loop ────────────────────────────────────────────────────────────────

echo "Trimming changelogs to relevant version ranges..."
if [[ "$DRY_RUN" == true ]]; then
    echo "(DRY RUN — no files will be modified)"
fi
echo ""

# Use process substitution instead of pipe to keep counters in main shell
while IFS=$'\t' read -r pkg _repo _ref filename; do
    [[ -z "$pkg" || "$pkg" == "# package" ]] && continue

    file="$CHANGELOGS_DIR/$pkg/$filename"
    if [[ ! -f "$file" ]]; then
        continue
    fi

    TOTAL=$((TOTAL + 1))

    # Get base version
    base_version=$(get_base_version "$pkg")
    if [[ -z "$base_version" ]]; then
        echo "$pkg/$filename:"
        echo "  No base version in composer-diff.json (new install?), keeping as-is"
        echo ""
        SKIPPED=$((SKIPPED + 1))
        continue
    fi

    base_norm=$(normalize_version "$base_version")

    echo "$pkg/$filename:"
    echo "  Range: $base_version → $(jq -r --arg p "$pkg" '.packages[$p].version_target' "$DIFF_FILE")"

    # Trim old versions (UNRELEASED sections are preserved — they sit above
    # version headers and contain upgrade-relevant content, e.g. Oro 7.0 changes)
    detect_and_trim "$file" "$base_norm" "$pkg" "$filename"

    case "$TRIM_RESULT" in
        "$RESULT_TRIMMED") TRIMMED=$((TRIMMED + 1)) ;;
        "$RESULT_EMPTIED") EMPTIED=$((EMPTIED + 1)) ;;
        "$RESULT_KEPT")    KEPT_AS_IS=$((KEPT_AS_IS + 1)) ;;
    esac

    echo ""
done < <(tail -n +2 "$TSV_FILE")

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Total files processed: $TOTAL"
echo "  Trimmed:    $TRIMMED"
echo "  Emptied:    $EMPTIED"
echo "  Kept as-is: $KEPT_AS_IS"
echo "  Skipped:    $SKIPPED"
