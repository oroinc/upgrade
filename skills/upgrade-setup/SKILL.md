---
name: upgrade-setup
description: "Set up both application instances with Docker, database, and warmed cache. Capture DI container dump for analysis. Both instances get identical DB state."
disable-model-invocation: false
allowed-tools: Bash, Read, Write, Edit, Glob, Grep, AskUserQuestion
---

# /upgrade-setup — Set Up Application Instances

Invoke after `/upgrade-init`.

## Progress Tracking

Update `.ai-upgrade/progress.json`: set `"in-progress"` on entry with `startedAt`, `"completed"` on success with `completedAt` and summary in `notes`, `"failed"`/`"blocked"` on error. Update `notes` with current sub-step during long operations.

## Pre-collected Answers

Check `.ai-upgrade/answers.json` before asking — use non-null values silently.

| Question | answers.json key | Value mapping |
|----------|-----------------|---------------|
| DB dump or fresh install (Step 2) | `preferences.installMethod` | `"dump-restore"` / `"clean-install"` |
| Database dump path (Step 2) | `inputs.databaseDumpPath` | String path |

## Procedure

### Step 1 — Read config

Read `.ai-upgrade/config.json` — extract `paths.currentApp`, `paths.newApp`, `docker.currentComposeProject`, `docker.newComposeProject`, `database.dumpFilePath`, `installMethod`.

### Step 1b — Git Preamble

Ensure workspace root is a git repo: `git rev-parse --git-dir 2>/dev/null`. If not → `git init`, create `.gitignore` (`.ai-upgrade/*.sql.gz`, `application/*/vendor/`, `application/*/node_modules/`, `application/*/var/`, `*.sql`, `*.sql.gz`), initial commit.

### Step 2 — Ask for database dump

If `database.dumpFilePath` is null, ask: provide dump path or fresh `oro:install`. Update config accordingly.

### Step 3 — Set up `application/current/`

**3a. Start Docker + wait:**
```bash
cd <currentApp> && docker compose up -d pgsql
cd <currentApp> && docker compose exec pgsql pg_isready -U <POSTGRES_USER>
```

Retry readiness a few times with 2s intervals.

**3a-bis. Install vendor:**
```bash
cd <currentApp> && COMPOSER_PROCESS_TIMEOUT=0 symfony composer install \
  --prefer-dist --no-dev --no-scripts --no-plugins --ignore-platform-reqs --no-ansi 2>&1
```

**3b.** Read DB credentials from `docker-compose.yml` pgsql service (or `.env-app`/`.env.local`).

**3c. Populate database:**

**Dump restore:**
```bash
cd <currentApp> && docker compose exec -T pgsql createdb -U <USER> <DB> 2>/dev/null; true
gunzip -c <dump> | docker compose exec -T pgsql psql -U <USER> -d <DB>
```

**Fresh install** (no Bash timeout — 10-30 min):
```bash
cd <currentApp> && rm -rf var/cache/prod && ORO_ENV=prod symfony php bin/console oro:install \
  --user-name=admin --user-email=admin@example.com --user-firstname=John --user-lastname=Doe \
  --user-password=admin --organization-name=Oro --timeout=0 --sample-data=n \
  --language=en --formatting-code=en_US --application-url=https://127.0.0.1:8000
```

**3d. Warm cache** (if restored from dump):
```bash
cd <currentApp> && ORO_ENV=prod symfony php bin/console cache:warmup
```

### Step 4 — Capture DI container dump

Use `--show-hidden` (not `--show-private` — doesn't exist in Symfony 6.4+):
```bash
cd <currentApp> && ORO_ENV=prod symfony php bin/console debug:container \
  --show-hidden --format=json > /tmp/.ai-upgrade-container-dump-raw.json 2>/tmp/.ai-upgrade-container-dump-err.log
```

Parse JSON: build services map (`service_id → FQCN`), extract decorators (`serviceId`, `class`, `decorates`, `decoratedClass`), compute stats.

Write `.ai-upgrade/container-dump.json`. Clean up temp files. Stop on failure — dump is critical for `/upgrade-analyze`.

### Step 5 — Create database dump for new instance

```bash
cd <currentApp> && docker compose exec -T pgsql pg_dump -U <USER> -d <DB> \
  --no-owner --no-acl | gzip > <aiUpgradeRoot>/.ai-upgrade/db-dump.sql.gz
```

Update `database.dumpFilePath` in config to this path.

### Step 6 — Set up `application/new/`

**6a.** Start Docker pgsql, wait for readiness.

**6a-bis. Install vendor** (from existing lock — pre-upgrade deps):
```bash
cd <newApp> && COMPOSER_PROCESS_TIMEOUT=0 symfony composer install \
  --prefer-dist --no-dev --no-scripts --no-plugins --ignore-platform-reqs --no-ansi 2>&1
```

**6b. Restore DB** from Step 5 dump.

**6c. Warm cache** (non-blocking — may fail on pre-upgrade app).

### Step 7 — Commit state files

Skip if nothing changed:
```bash
git add .ai-upgrade/config.json .ai-upgrade/container-dump.json .ai-upgrade/progress.json && \
  git diff --cached --quiet || git commit -m "upgrade-setup: capture container dump and config"
```

NOT `.env` files (local Docker config), NOT `db-dump.sql.gz` (binary, gitignored).

### Step 8 — Report results

Summary: both instances (Docker status, DB status, cache status), container dump stats (total services, with class, decorators). Show first 30 decorators.

Next: `/upgrade-theme-migration` (or skip to `/upgrade-merge-upstream` if no custom themes).
