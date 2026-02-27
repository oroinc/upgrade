---
name: upgrade-theme-migration
description: "Preserve current theme by running oro:theme:migrate on the old instance, then copy the generated bundle to application/new/ and activate it."
disable-model-invocation: false
allowed-tools: Bash, Read, Write, Edit, Glob, Grep, AskUserQuestion
---

# /upgrade-theme — Preserve & Migrate Theme

Requires `/upgrade-setup` completed. Runs before `/upgrade-merge-upstream`.

## Progress Tracking

Update `.ai-upgrade/progress.json`: set `"in-progress"` on entry with `startedAt`, `"completed"` on success with `completedAt` and summary in `notes`, `"failed"`/`"blocked"` on error. Update `notes` with checkpoints during long operations.

## Pre-collected Answers

Check `.ai-upgrade/answers.json` before asking — use non-null values silently.

| Question | answers.json key | Value mapping |
|----------|-----------------|---------------|
| Layout consolidation (Step 5) | `preferences.themeLayoutConsolidation` | `"proceed-as-is"` / `"move-to-custom-theme"` |
| Asset duplicates (Step 8) | `preferences.themeAssetDuplicates` | `"remove"` / `"keep"` |

## Procedure

### Step 1 — Read config

Read `.ai-upgrade/config.json` — extract `paths.currentApp`, `paths.newApp`.

**Parallel execution:** After reading config, run Steps 2, 4, and 5 detection commands in a single message, then process results together.

### Step 1b — Git Preamble

Ensure workspace root is a git repo: `git rev-parse --git-dir 2>/dev/null`. If not → `git init`, create `.gitignore` (`.ai-upgrade/*.sql.gz`, `application/*/vendor/`, `application/*/node_modules/`, `application/*/var/`, `*.sql`, `*.sql.gz`), initial commit.

### Step 2 — Detect custom themes

```bash
find <currentApp>/src -name "theme.yml" -path "*/layouts/*" -not -path "*/ThemeDefault*"
```

Read each `theme.yml`, extract theme ID (directory name under `layouts/`). If none found, continue — migration still generates the base bundle.

### Step 4 — Verify old instance is ready

Check Docker pgsql running (`docker compose ps`), start if needed. Check cache exists (`test -d <currentApp>/var/cache/prod`), warm if missing.

### Step 5 — Pre-migration layout consolidation audit

```bash
find <currentApp>/src -path "*/layouts/blank/*" -type f -not -path "*/ThemeDefault*"
find <currentApp>/src -path "*/layouts/default/*" -type f -not -path "*/ThemeDefault*"
```

If found, report grouped by bundle. Ask: **Proceed as-is** (default — migration captures `default/` overrides) or **Move to custom theme first** (cleaner separation).

If moving: `rsync` files from `layouts/default/` → `layouts/<customThemeId>/` in both app dirs, `rm -rf` originals, clear and rewarm cache on old instance.

Skip silently if no files found.

### Step 6 — Install oro/theme-migration on old instance

```bash
cd <currentApp> && COMPOSER_PROCESS_TIMEOUT=0 symfony composer require --dev oro/theme-migration \
  --no-scripts --no-plugins --ignore-platform-reqs
```

If fails, retry with `--with-all-dependencies` (warn about version drift — show `git diff composer.lock --stat`).

### Step 7 — Run theme migration on old instance

```bash
cd <currentApp> && rm -rf var/cache public/bundles
cd <currentApp> && ORO_ENV=prod symfony php bin/console assets:install
cd <currentApp> && ORO_ENV=prod symfony php bin/console oro:theme:migrate
```

No Bash timeout. Verify bundle generated: `ls -la <currentApp>/src/Oro/Bundle/ThemeDefault*/`.

**Capture actual names** (source of truth for all subsequent steps):
1. Bundle dir name → `BUNDLE_NAME` (e.g., `ThemeDefault60Bundle`)
2. Theme ID from generated `theme.yml` under `layouts/` → `THEME_ID` (e.g., `default_60`)

The command determines names internally — do NOT compute or assume them.

Show generated files: `find <currentApp>/src/Oro/Bundle/ThemeDefault* -type f | head -50`

### Step 8 — Duplicate public asset cleanup

Find project-source bundle aliases with matching dirs in the generated bundle's `Resources/public/`. Derive each alias from the `*Bundle.php` class name (strip `Bundle` suffix, lowercase). Only flag project-source duplicates (vendor assets must stay). If found → ask Remove (recommended) / Keep. Skip if none.

### Step 9 — Copy generated bundle to new instance

```bash
mkdir -p <newApp>/src/Oro/Bundle/
cp -R <currentApp>/src/Oro/Bundle/<BUNDLE_NAME> <newApp>/src/Oro/Bundle/
```

Bundle registration is automatic via bundled `bundles.yml`.

### Step 10 — Run yml:fix on new instance

If `<newApp>/bin/upgrade-toolkit` exists:
```bash
cd <newApp> && ORO_ENV=prod symfony php bin/upgrade-toolkit yml:fix \
  --source=src/Oro/Bundle/<BUNDLE_NAME>/Resources/
```

If not installed, note in summary — user can run after `/upgrade-toolkit`.

### Step 11 — Update custom theme configuration

Find custom `theme.yml` files in `<newApp>/src` (excluding ThemeDefault*). For each, ask which should use the preserved theme as parent. Update selected files:

```yaml
parent: <THEME_ID>
resolve_extra_paths:
    - /bundles/orothemedefault<XX>
```

Where `<XX>` is derived from `THEME_ID` (e.g., `default_60` → `60`).

### Step 12 — Report summary and next steps

Summary: generated bundle name, theme ID, source/dest paths, registration method, parent set, layout audit results, asset duplicates, yml:fix status.

### Step 12b — Commit theme migration

In `application/new/` — skip if nothing changed:
```bash
cd <newApp> && git add src/ && git diff --cached --quiet || git commit -m "upgrade-theme-migration: add generated theme bundle"
```

Update refs in root:
```bash
git add .ai-upgrade/progress.json application/new && git diff --cached --quiet || git commit -m "Update submodule refs after upgrade-theme-migration"
```

Next: `/upgrade-merge-upstream`.
