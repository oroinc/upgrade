---
name: upgrade-build
description: "Iterative container build fix loop using changelogs and upgrade-toolkit, then run oro:platform:update. Documents all manual edits in manual-edits.md."
disable-model-invocation: false
allowed-tools: Bash, Read, Write, Edit, Glob, Grep, AskUserQuestion
---

# /upgrade-build — Iterative Container Build & Platform Update

Invoke after `/upgrade-toolkit` has run Rector rules and code style fixers.

## Progress Tracking

Update `.ai-upgrade/progress.json`: set `"in-progress"` on entry with `startedAt`, `"completed"` on success with `completedAt` and summary in `notes`, `"failed"`/`"blocked"` on error. Update `notes` with checkpoints during long operations.

## Pre-collected Answers

Check `.ai-upgrade/answers.json` before asking — use non-null values silently.

| Question | answers.json key | Value mapping |
|----------|-----------------|---------------|
| Error max attempts action (Step 5b) | `policies.onBuildErrorMaxAttempts` | `"continue"` -> continue, `"stop"` -> stop and ask |

## Fix Principles

1. **Check both PHP and YAML** — PHP structural changes often need paired YAML service definition updates (tags, arguments, FQCN refs)
2. **Toolkit leaves YAML incomplete** — toolkit handles PHP but not service defs; check tags/arguments/FQCN after toolkit changes
3. **Bulk fix all occurrences** — grep `src/` AND `packages/` for the pattern, fix everything in one pass
4. **Errors surface at different depths** — DI container check, cache warmup (routing/Twig/Doctrine), platform update post-migration (lazy services, cron, workflows)
5. **Vendor bugs → patches** — fix via `cweagans/composer-patches` (see "Applying a vendor patch" below)

## Procedure

### Step 1 — Read config & detect environment

1. Read `.ai-upgrade/config.json` — extract `paths.newApp`, `paths.aiUpgradeRoot`, `paths.packages`, `docker.newComposeProject`, `database.dumpFilePath`.
2. Check `which symfony` — use `symfony php`/`symfony composer` if found, else bare `php`/`composer`.
3. Identify PHP source targets: main app `<newApp>/src/` + package workspaces from `.ai-upgrade/custom-deps.json` (entries with non-null `packagePath`). PHP root: if `*Bundle.php` at package root → `packages/<name>/`, else `packages/<name>/src/`.
4. Read DB credentials (`POSTGRES_USER`, `POSTGRES_DB`, `POSTGRES_PASSWORD`) from `<newApp>/docker-compose.yml` pgsql service — do NOT assume defaults.

### Step 1b — Git Preamble

Ensure workspace root is a git repo: `git rev-parse --git-dir 2>/dev/null`. If not → `git init`, create `.gitignore` (`.ai-upgrade/*.sql.gz`, `application/*/vendor/`, `application/*/node_modules/`, `application/*/var/`, `*.sql`, `*.sql.gz`), initial commit.

### Step 2 — Ensure Docker services running

```bash
cd <newApp> && docker compose ps --format json 2>/dev/null
```

Start `pgsql` if not running. Wait for `pg_isready` (up to 10 retries, 3s intervals).

### Step 3 — Restore database dump

Read `database.dumpFilePath` from config. If null/missing → stop, tell user to run `/upgrade-setup`.

Drop and recreate DB, then restore (**no Bash timeout** — restores can take minutes):

```bash
cd <newApp> && docker compose exec -T pgsql dropdb -U <USER> --if-exists <DB>
cd <newApp> && docker compose exec -T pgsql createdb -U <USER> <DB>
gunzip -c <dump> | (cd <newApp> && docker compose exec -T pgsql psql -U <USER> -d <DB> -q)
```

For `.sql` files, use `< <dump>` instead of `gunzip -c`.

### Step 4 — Clear cache

```bash
rm -rf <newApp>/var/cache/prod/
```

### Step 5 — Iterative container build fix loop

Runs TWO validation commands in sequence — both must pass:
1. `ORO_ENV=prod symfony php bin/console oro:entity-extend:cache:check` (5-min Bash timeout)
2. `cache:warmup` (5-min Bash timeout) — validates routing, Twig extensions, Doctrine proxies

**Safeguards:** `maxAttemptsPerError = 2`, `maxTotalIterations = 15`, no-progress detection (same error after fix → escalate to user).

**Track:** `errorAttempts` map (signature → count), `iterationCount`, `fixLog` array.

#### 5a. Run checks

Run container check. If exit 0 → clear cache, run `cache:warmup`. If both exit 0 → break to Step 7.

Non-zero exit → parse error, proceed to 5b.

#### 5b. Parse the error

Extract: error type, FQCN, method/property, error signature (stable dedup key).

Check `errorAttempts[signature]` — if `>= maxAttemptsPerError`, log as unfixable, document in `manual-edits.md`, ask user whether to continue or stop. Check `iterationCount >= maxTotalIterations` → stop loop, report all remaining.

#### 5c. Search changelogs (CRITICAL — before every fix)

Map FQCN to changelog:

| Namespace pattern | Changelog path |
|---|---|
| `Symfony\Component\<Name>\*` | `changelogs/symfony/<name-lowercase>/CHANGELOG.md` |
| `Symfony\Bundle\<Name>\*` | `changelogs/symfony/<name-lowercase>/CHANGELOG.md` |
| `Symfony\Bridge\<Name>\*` | `changelogs/symfony/<name-lowercase>-bridge/CHANGELOG.md` |
| `Doctrine\DBAL\*` | `changelogs/doctrine/dbal/UPGRADE.md` |
| `Doctrine\ORM\*` | `changelogs/doctrine/orm/UPGRADE.md` |
| `Doctrine\Bundle\*` | `changelogs/doctrine/doctrine-bundle/UPGRADE-*.md` |
| `Doctrine\Common\Collections\*` | `changelogs/doctrine/collections/UPGRADE.md` |
| Other vendors | `changelogs/<vendor-lowercase>/<package>/CHANGELOG.md` or `UPGRADE.md` |

Search for short class name + method name, read 15-25 lines of context. If not found, broaden to full `changelogs/` directory. Note file path and quote for `manual-edits.md`.

#### 5d. Apply the fix

Do NOT re-run the toolkit (already ran before this skill).

**Diagnosis checklist:**
1. Read failing source code — don't guess from error alone
2. Determine fix location: PHP class AND its YAML service definition
3. Check if toolkit made a related PHP change that needs a YAML counterpart
4. Search ALL occurrences across `src/` AND `packages/`, fix in one pass

**Common Oro/Symfony upgrade patterns** (apply in bulk):
- `type: annotation` → `type: attribute` in routing YAML (Symfony 7)
- Remove `twig.extension` service tag when toolkit converted to `#[AsTwigFunction]` attributes — Symfony's `AttributeExtensionPass` enforces mutual exclusivity with `extends AbstractExtension`
- `getWrappedConnection()` → `getNativeConnection()` (Doctrine DBAL 4)
- `Sensio\...\Template` → `Symfony\Bridge\Twig\Attribute\Template` — when adding explicit template paths, use the **Twig namespace** (strip `Bundle` suffix): `@MagecoreApplication/Action/view.html.twig` NOT `@MagecoreApplicationBundle/...`. Run `debug:twig` to list registered namespaces. YAML `resource: "@BundleName/Controller"` keeps the full name (kernel resource locator), only `#[Template]` uses the short Twig namespace.

Clear cache after fixes. Document in `manual-edits.md` (Step 6). Loop back to 5a.

### Step 6 — Maintain manual-edits.md

Append entry for every manual edit to `<newApp>/manual-edits.md`:

```markdown
## [<iteration>] <short error description>
**Error:** `<message>`
**File(s):** `src/Path/To/Class.php`
**Change:** <description>
**Changelog:** > <quote> — `changelogs/<vendor>/<package>/CHANGELOG.md`
**Reasoning:** <why>
```

Prefix `[packages/<name>]` for package errors.

### Step 7 — Save build state

Write `.ai-upgrade/build-state.json` with `containerBuild` results (`success`, `iterations`, `errorsFixed`, `errorsDocumented`, `fixLog`). Set `platformUpdate: null`.

### Step 8 — Platform update

```bash
cd <newApp> && ORO_ENV=prod symfony php bin/console oro:platform:update \
  --skip-download-translations --skip-translations --force --timeout=0 2>&1
```

**No Bash timeout** (10-30 min). Fix loop up to 20 iterations on failure.

**PHP/service errors** (type errors, missing methods, interface violations): handle same as container build (5b–5d). Platform update is idempotent — fix code, clear cache, re-run. **No DB restore needed.** Fixes in `packages/` take effect immediately (symlinked). Fixes in vendor require a patch.

**DB migration errors:**
- **"Column/table already exists"**: Read migration source, check guard condition. Wrong guard → **vendor patch** + restore DB from clean dump + re-run. Correct guard but pre-existing schema → `INSERT INTO oro_migrations (bundle, version, loaded_at) VALUES ('<Bundle>', '<version>', NOW())`. Prefer patches over marking as applied.
- **"Column/table does not exist"**: Check for skipped prerequisite migration. Try `oro:migration:load --force --bundles=<Bundle> --timeout=0`.
- **"Duplicate key"**: Check if already migrated, mark complete if so.
- **Unrecoverable**: Document in `manual-edits.md` with full context and investigative SQL.

Update `build-state.json` after each attempt.

### Step 9 — Commit build fixes

In `application/new/` — skip if nothing changed:
```bash
cd <newApp> && git add -A src/ config/ patches/ composer.json manual-edits.md && \
  git diff --cached --quiet || git commit -m "upgrade-build: fix container build and platform update errors"
```

Each package — skip if nothing changed:
```bash
git -C packages/<name> add -A && git -C packages/<name> diff --cached --quiet || \
  git -C packages/<name> commit -m "upgrade-build: fix container build errors"
```

Update state and refs in root:
```bash
git add .ai-upgrade/build-state.json .ai-upgrade/progress.json application/new packages/ 2>/dev/null; \
  git diff --cached --quiet || git commit -m "upgrade-build: update state and submodule refs"
```

### Step 10 — Summary & next steps

Present consolidated summary (container build iterations/status + platform update iterations/status). Advise:
- Review `manual-edits.md` — verify each change
- Next: `/upgrade-analyze`

## Applying a vendor patch

1. Create `<newApp>/patches/<name>.patch` (unified diff, paths relative to package root)
2. Add to `composer.json` under `extra.patches.<vendor/package>`
3. Apply: `rm -rf <newApp>/vendor/<vendor>/<package>` then `COMPOSER_PROCESS_TIMEOUT=0 composer install --no-scripts --prefer-dist --ignore-platform-reqs` (do NOT use `composer reinstall` — fails on VCS-sourced packages)
4. Verify by reading the patched vendor file
5. For DB migration patches: restore DB from clean dump before re-running platform update
6. Clear cache before re-running, document in `manual-edits.md`
