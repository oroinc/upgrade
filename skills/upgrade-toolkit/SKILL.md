---
name: upgrade-toolkit
description: "Run oro/upgrade-toolkit for automatic PHP migration and code style fixers on application/new/src/ and all package workspaces in packages/."
disable-model-invocation: false
allowed-tools: Bash, Read, Write, Edit, Glob, Grep, AskUserQuestion
---

# /upgrade-toolkit — Automatic PHP migration & code style

Invoke after `/upgrade-patches` (or after `/upgrade-composer` if no patches).

## Progress Tracking

Update `.ai-upgrade/progress.json`: set `"in-progress"` on entry with `startedAt`, `"completed"` on success with `completedAt` and summary in `notes`, `"failed"`/`"blocked"` on error. Update `notes` with checkpoints during long operations.

## Procedure

### Step 1 — Read config & identify targets

1. Read `.ai-upgrade/config.json` — extract `paths.newApp`, `paths.aiUpgradeRoot`, `paths.packages`.
2. Check `which symfony` — use `symfony php`/`symfony composer` if found, else bare.
3. Identify PHP source targets: main app `<newApp>/src/` + package workspaces from `.ai-upgrade/custom-deps.json` (entries with non-null `packagePath`). PHP root: if `*Bundle.php` at package root → `packages/<name>/`, else `packages/<name>/src/`.
4. Present inventory with file counts.

### Step 2 — Pre-flight: check for uncommitted changes

```bash
git -C <newApp> status --porcelain
git -C <packagesRoot>/<name> status --porcelain  # each package
```

If **any** target has uncommitted changes → **STOP**. Toolkit/cs-fixer/phpcbf modify files in-place; uncommitted work would be tangled with automated changes.

### Step 3 — Install the upgrade toolkit

```bash
cd <newApp> && COMPOSER_PROCESS_TIMEOUT=0 <composer-cmd> require oro/upgrade-toolkit:dev-master \
  --dev --no-scripts --ignore-platform-reqs --no-ansi 2>&1
```

Do NOT use `--no-plugins` here — toolkit installs normally. Verify: `ls <newApp>/bin/upgrade-toolkit`.

### Step 4 — Run upgrade toolkit on main app

```bash
cd <newApp> && <php-cmd> bin/upgrade-toolkit 2>&1 | tail -100
```

Only the last ~100 lines matter (summary). Look for: `[OK] N files were changed by Rector`, `N .yml files with changes`. For re-runs add `--use-cache`.

### Step 5 — Run upgrade toolkit on packages

For each package PHP root:
```bash
cd <newApp> && <php-cmd> bin/upgrade-toolkit --source=<absolute-package-php-root> 2>&1 | tail -30
```

"Invalid source directory" from YAML fixer is expected for standalone packages — only Rector matters.

### Step 5b — Capture changed files for code style

Collect toolkit-changed PHP files (code style fixers only run on these):

```bash
git -C <newApp> diff --name-only -- 'src/*.php' | head -500
git -C <packagesRoot>/<name> diff --name-only -- '*.php' | head -500  # each package
```

If no PHP files changed in a target, skip code style for it.

### Step 6 — Code style: php-cs-fixer + phpcbf (main app + packages)

Run **only on toolkit-changed files** from Step 5b. For each target (main app, then each package): run both fixers in sequence, skip if no changed files, batch if 100+ files. Use absolute paths for packages.

**php-cs-fixer:**
```bash
cd <newApp> && <php-cmd> bin/php-cs-fixer fix \
  <file1> <file2> ... \
  --config=vendor/oro/platform/build/.php-cs-fixer.php --no-ansi 2>&1
```

**phpcbf:**
```bash
cd <newApp> && <php-cmd> bin/phpcbf \
  <file1> <file2> ... \
  -p --encoding=utf-8 --extensions=php \
  --standard=vendor/oro/platform/build/Oro --no-colors 2>&1
```

Binary fallback: try `vendor/bin/` if not at `bin/`. phpcbf exit codes 0, 1, 2 = success (3 = runtime error). Do NOT use `phpcs.xml` as standard — point directly at the `Oro` directory.

### Step 7 — Save results

Write `.ai-upgrade/toolkit-changes.json` with per-target counts (toolkit files, cs-fixer fixed, phpcbf fixed).

### Step 8 — Commit toolkit changes

In `application/new/` — skip if nothing changed:
```bash
cd <newApp> && git add -A src/ && git diff --cached --quiet || \
  git commit -m "upgrade-toolkit: apply Rector rules and code style fixes"
```

Each package — skip if nothing changed:
```bash
git -C packages/<name> add -A && git -C packages/<name> diff --cached --quiet || \
  git -C packages/<name> commit -m "upgrade-toolkit: apply Rector rules and code style fixes"
```

Update state and refs in root:
```bash
git add .ai-upgrade/toolkit-changes.json .ai-upgrade/progress.json application/new packages/ 2>/dev/null; \
  git diff --cached --quiet || git commit -m "upgrade-toolkit: update state and submodule refs"
```

### Step 9 — Summary & next step

Present consolidated summary (main app + per-package results).

**Not covered by toolkit:** Twig templates, JS/SCSS, YAML config (datagrid, security). Next: `/upgrade-build`.
