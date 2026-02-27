---
name: upgrade-patches
description: "Review vendor patches (cweagans/composer-patches) against new vendor versions. Run composer install with the plugin enabled, parse its output to classify patches as APPLIED/FAILED, then attempt to fix failed patches. Skip if no vendor patches."
disable-model-invocation: false
allowed-tools: Bash, Read, Write, Edit, Glob, Grep, AskUserQuestion
---

# /upgrade-patches — Review vendor patches

Invoke after `/upgrade-composer`, only if vendor patches were detected.

## Progress Tracking

Update `.ai-upgrade/progress.json`: set `"in-progress"` on entry with `startedAt`, `"completed"` on success with `completedAt` and summary in `notes`, `"blocked"` if patches remain unfixable.

## Procedure

### Step 1 — Read config & collect patch inventory

1. Read `.ai-upgrade/config.json` — extract `paths.currentApp`, `paths.newApp`, `paths.aiUpgradeRoot`. If `detection.hasPatchPlugin` is false → stop, proceed to `/upgrade-toolkit`.
2. Read `<newApp>/composer.json` → extract `extra.patches` (and `extra.patches-file` if present).
3. Read old versions from `<currentApp>/composer.lock`, new from `<newApp>/composer.lock`.
4. Present inventory: total patches, packages with old → new version.

### Step 1b — Git Preamble

Ensure workspace root is a git repo: `git rev-parse --git-dir 2>/dev/null`. If not → `git init`, create `.gitignore` (`.ai-upgrade/*.sql.gz`, `application/*/vendor/`, `application/*/node_modules/`, `application/*/var/`, `*.sql`, `*.sql.gz`), initial commit.

### Step 2 — Run composer install with patch plugin

```bash
cd <newApp> && COMPOSER_PROCESS_TIMEOUT=0 composer install \
  --prefer-dist --no-dev --no-scripts --ignore-platform-reqs --no-ansi 2>&1
```

Do **NOT** use `--no-plugins` — the point is to let `cweagans/composer-patches` run.
Do **NOT** set `COMPOSER_EXIT_ON_PATCH_FAILURE=1` — let it skip failures for a complete picture.
Save full output.

### Step 3 — Classify patches from output

Parse composer output:
- Patch line under `Applying patches for <pkg>` followed by normal continuation → **APPLIED**
- Patch line followed by `Could not apply patch! Skipping.` → **FAILED**

Cross-reference against inventory to ensure all patches accounted for.

### Step 4 — Attempt to fix failed patches

For each FAILED patch:
1. Read patch file + target vendor file
2. Diagnose: method/API rename, line offset shift, code restructured, or fix already upstream
3. **Obsolescence check:** if all `+` lines already exist in target → **OBSOLETE** (mark for removal)
4. If fixable: update patch, verify with `git apply --check`
5. If not fixable: leave as FAILED, note what needs manual attention

### Step 5 — Re-run if patches were fixed

Re-run Step 2 to confirm zero failures. Loop to Step 4 for any new failures.

### Step 6 — Generate report

Write `.ai-upgrade/patches-report.json` with per-patch status (APPLIED/FAILED/OBSOLETE), action, and details.

Present summary table. Advise:
- **FAILED**: needs manual re-creation
- **OBSOLETE**: remove from `extra.patches`
- **APPLIED**: verify still needed (check package CHANGELOG)

After resolved → proceed to `/upgrade-toolkit`.

### Step 7 — Commit patch changes

In `application/new/` — skip if nothing changed:
```bash
cd <newApp> && git add -A patches/ composer.json && git diff --cached --quiet || \
  git commit -m "upgrade-patches: update vendor patches for Oro 7.0"
```

Update state and refs in root:
```bash
git add .ai-upgrade/patches-report.json .ai-upgrade/progress.json application/new && \
  git diff --cached --quiet || git commit -m "upgrade-patches: update state and submodule ref"
```
