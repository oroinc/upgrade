---
name: upgrade-init
description: "Initialize Oro upgrade workspace — clone repo as submodules (current + new), detect edition/versions from composer.json, set up Docker isolation, write config.json."
disable-model-invocation: false
allowed-tools: Bash, Read, Write, Edit, Glob, Grep, AskUserQuestion
---

# upgrade-init — Initialize Oro Upgrade Workspace

## Progress Tracking

Update `.ai-upgrade/progress.json`: set `"in-progress"` on entry with `startedAt` (create with all steps `"pending"` if missing), `"completed"` on success with `completedAt` and summary in `notes`, `"failed"`/`"blocked"` on error.

## Pre-collected Answers

Check `.ai-upgrade/answers.json` before asking — use non-null values silently.

| Question | answers.json key | Value mapping |
|----------|-----------------|---------------|
| Resume vs start fresh (Step 1) | `policies.onExistingState` | `"resume"` -> Resume, `"start-fresh"` -> Start fresh |
| Repository URL (Step 2) | `inputs.repositoryUrl` | String value used directly |
| Base branch (Step 2) | `inputs.baseBranch` | String value used directly |
| Database dump path (Step 2) | `inputs.databaseDumpPath` | String path or `null` for skip |
| Branch conflict (Step 5) | `policies.onBranchConflict` | `"use-existing"` -> Use existing, `"rename"` -> Rename |

## Step 1: Check for Existing Configuration

Read `.ai-upgrade/config.json`. If exists with `"status": "initialized"`: show summary, ask Resume/Start fresh. Resume → skip to Step 13. Start fresh → `rm -rf .ai-upgrade`.

## Step 2: Collect Input (3 Questions)

Ask all 3 in a **single** AskUserQuestion call (free-text via "Other"):

1. **Repository URL** — Git URL (HTTPS or SSH). Convert HTTPS to SSH if needed. Validate with `git ls-remote`.
2. **Base branch** — e.g., `master`, `main`, `develop`. Validate with `git ls-remote --heads`.
3. **Database dump path** — absolute path or skip. Validate file exists.

## Step 3: Initialize Git Workspace

Check if working dir is a git repo (`git rev-parse --git-dir`).

**If NOT:** init, create `.gitignore` (exclude `.ai-upgrade/`, `application/*/vendor/`, `application/*/node_modules/`, `application/*/var/`, `application/*/public/bundles/`, `application/*/public/build/`, `*.sql`, `*.sql.gz`), create initial commit with `.gitignore CLAUDE.md .claude/`.

**If yes:** ensure `.gitignore` has `application/*/vendor/` pattern.

## Step 4: Add `application/current/` as Submodule

Check: `git submodule status application/current 2>/dev/null`

**New:** `git submodule add <ssh-url> application/current`, then checkout user's branch.
**Exists:** verify remote URL and branch match, fix if needed.

## Step 5: Create Upgrade Branch and Add `application/new/`

### 5.1 Create upgrade branch

Check if `feature/upgrade-7.0` exists locally or on remote.

- **Doesn't exist:** create from current, push with `-u`, checkout base branch.
- **Exists on remote only:** ask user — Use existing or Rename.
- **Exists locally:** push if not on remote.

### 5.2 Add `application/new/` submodule

Same pattern as Step 4 but checkout `feature/upgrade-7.0`.

After this: `application/current/` on `<base-branch>`, `application/new/` on `feature/upgrade-7.0`.

## Step 6: Detect Application Config from `composer.json`

Read `application/current/composer.json`:

1. **`name`** → store as `composerName`
2. **`require.php`** → extract major.minor (minimum version if ambiguous)
3. **`require.oro/*`** → extract version (prefer `oro/platform` or `oro/commerce`)
4. **Patch plugin** → check `require`/`require-dev` for `cweagans/composer-patches` or `vaimo/composer-patches`
5. **Config style** → check for `config/parameters.yml.dist` (v5.x) or `.env-app` (v6+)
6. **Custom themes** → `find src -name "theme.yml" -path "*/layouts/*" -not -path "*/ThemeDefault*"`

## Step 7: Detect Upstream Oro Application Repo

Map `name` to upstream:

| Composer `name` | Upstream Repo | Edition |
|---|---|---|
| `oro/commerce-crm-enterprise-application` | `oroinc/orocommerce-enterprise-application` | OroCommerce EE (with CRM) |
| `oro/commerce-enterprise-application` | `oroinc/orocommerce-enterprise-application` | OroCommerce EE |
| `oro/commerce-enterprise-nocrm-application` | `oroinc/orocommerce-enterprise-nocrm-application` | OroCommerce EE (no CRM) |
| `oro/commerce-application` | `oroinc/orocommerce-application` | OroCommerce CE |
| `oro/crm-enterprise-application` | `oroinc/crm-enterprise-application` | OroCRM EE |
| `oro/crm-application` | `oroinc/crm-application` | OroCRM CE |
| `oro/platform-application` | `oroinc/platform-application` | OroPlatform CE |

SSH URL: `git@github.com:<upstream-repo>.git`. If not in mapping → ask user.

## Step 8: Resolve Target Version Tag

Verify `7.0.0-rc` exists: `git ls-remote --tags <upstream> 'refs/tags/7.0.0-rc'`. If not found → stop.

## Step 9: Verify `.php-version` and `docker-compose.yml`

**`.php-version`:** Check both app dirs. If missing in `current/` → STOP, ask user to add. Read content for config.

**`docker-compose.yml`:** Check for `.yml` or `.yaml` in `current/`. If neither → STOP, ask user to add. Store the filename found.

## Step 10: Set Up Docker Compose Isolation

Ensure `.env` in each app dir has `COMPOSE_PROJECT_NAME`:
- `application/current/.env` → `oro-upgrade-current`
- `application/new/.env` → `oro-upgrade-new`

Start PostgreSQL for current app only (find service name from docker-compose file first):
```bash
docker compose -f ./application/current/docker-compose.yml up -d <pgsql-service>
```

## Step 11: Write `.ai-upgrade/config.json`

```bash
mkdir -p .ai-upgrade
```

Write config with: `version: 2`, `appEdition`, `composerName`, `currentVersion`, `targetVersion: "7.0.0-rc"`, `repositoryUrl`, `baseBranch`, `upgradeBranch`, `oroUpstreamRepo`, `paths` (aiUpgradeRoot, packages, currentApp, newApp), `php` (current + target `"8.4"`), `installMethod`, `database.dumpFilePath`, `docker` (compose project names), `detection` (hasPatchPlugin, hasCustomThemes, configStyle), legacy keys (`projectRoot`, `oldWorktreePath`, `newWorktreePath`), `createdAt`, `status: "initialized"`.

Read back to confirm.

## Step 12: Commit Submodule Setup

```bash
git add .gitmodules application/current application/new .gitignore && git commit -m "Add application submodules for upgrade workspace"
```

## Step 13: Report Summary & Next Steps

Print summary: application info, workspace paths, PHP versions, install method, DB dump, config style, patch plugin, upstream repo, Docker status.

Next: `/upgrade-setup` to set up both instances (Docker, DB, vendor, cache, container dump).
