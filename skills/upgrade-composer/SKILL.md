---
name: upgrade-composer
description: "Resolve dependencies for Oro 7.0 upgrade — methodical approach: optimistic attempt, extract custom deps, establish Oro-only baseline, probe each dep for minimum compatible version, set up package workspaces for unresolvable deps."
disable-model-invocation: false
allowed-tools: Bash, Read, Write, Edit, Glob, Grep, AskUserQuestion
---

# /upgrade-composer — Resolve Dependencies

All operations happen in `application/new/` only. Patches (`extra.patches`) are **not touched** — handled by `/upgrade-patches`. Invoke after `/upgrade-merge-upstream`.

## Progress Tracking

Update `.ai-upgrade/progress.json`: set `"in-progress"` on entry with `startedAt`, `"completed"` on success with `completedAt` and summary in `notes`, `"failed"`/`"blocked"` on error. Update `notes` with probe progress during long operations.

## Pre-collected Answers

Check `.ai-upgrade/answers.json` before asking — use non-null values silently.

| Question | answers.json key | Value mapping |
|----------|-----------------|---------------|
| Resume vs start fresh (Step 2) | `policies.onExistingState` | `"resume"` / `"start-fresh"` |
| Unresolvable dep action (Step 8) | `policies.onUnresolvableDep` | `"package-workspace"` / `"ask"` |

## Composer Flags

All composer commands use: `COMPOSER_PROCESS_TIMEOUT=0 <cmd> <subcommand> --no-plugins --no-scripts --ignore-platform-reqs`. `--no-plugins` is **critical** — disables `cweagans/composer-patches` which otherwise re-extracts packages and fails on 7.0 code. During probing, add `--no-install` (updates only json+lock, seconds per probe).

## Procedure

### Step 1 — Read config & check prerequisites

Read `.ai-upgrade/config.json` — extract `paths.newApp`, `paths.aiUpgradeRoot`, `targetVersion`, `detection.hasPatchPlugin`.

**Detect Composer:** `which symfony` → `symfony composer` if found, else bare `composer`.

**Check private repo auth:** scan `<newApp>/composer.json` for VCS repos on non-GitHub hosts, test with `git ls-remote`. Stop if auth fails.

**Verify upgrade branch:** no unmerged files (`git diff --name-only --diff-filter=U`), upstream merge exists (`git log --merges -5`), `oro/platform` has `7.0.*` constraint.

**Ensure workspace root is git repo:** `git rev-parse --git-dir 2>/dev/null`. If not → `git init`, create `.gitignore` (`.ai-upgrade/*.sql.gz`, `application/*/vendor/`, `application/*/node_modules/`, `application/*/var/`, `*.sql`, `*.sql.gz`), initial commit.

### Step 2 — Check for previous session

If `.ai-upgrade/custom-deps.json` exists → ask Resume/Start fresh. Resume: load file, skip resolved deps. Start fresh: restore `.ai-upgrade/composer.json.original` over `<newApp>/composer.json`.

### Step 3 — Optimistic attempt

```bash
rm -f <newApp>/composer.lock && rm -rf <newApp>/vendor
cd <newApp> && <composer-flags> update --no-dev --prefer-dist
```

No Bash timeout. Success → write minimal `custom-deps.json`, skip to Step 9. Failure (expected) → continue.

### Step 4 — Extract & classify custom dependencies

Compare `<newApp>/composer.json` against upstream: `git -C <newApp> show <targetVersion>:composer.json`.

**Baseline** (stays): upstream `require`, `cweagans/composer-patches`, `ext-*`, `oro/*` at `7.0.*` with VCS repos, upstream `repositories`, all `extra.patches`.

**Custom** (tracked separately): everything else in `require` + their VCS repos.

**Classifications:** `orolab-extension` (git.oroinc.com), `oro-adjacent` (oro/* not in upstream), `third-party-fork` (custom VCS for packagist pkg), `third-party` (standard).

Save `.ai-upgrade/custom-deps.json` and `.ai-upgrade/composer.json.original`.

### Step 5 — Establish Oro-only baseline

Remove custom deps + repos from `composer.json` (keep patches, patch plugin). Delete lock + vendor.

```bash
cd <newApp> && <composer-flags> update --no-dev --prefer-dist
```

If baseline fails → stop (upstream issue). On success → continue probing.

### Step 6 — Probe each custom dep

Ordered: `third-party` → `orolab-extension` → `third-party-fork`.

**6a.** Add VCS repo if needed: `composer config repositories.<key> vcs <url>`
**6b.** Probe: `<composer-flags> require --update-no-dev --no-install <dep>:<constraint>`
**6c.** On failure, find minimum compatible version:
- `dev-maintenance/X.Y` branches → try `dev-maintenance/7.0`, `7.0.*`, `dev-main`, `dev-master`
- Pinned versions → try incrementally (next minor/major)
- Semver ranges → re-probe, then next minor
**6d.** If `needs-package-upgrade`: remove temp repo (`config --unset repositories.<key>`)
**6e.** Update `custom-deps.json` after each dep
**6f.** Print progress: `[N/M] <dep> <constraint> ... RESOLVED|VERSION-BUMPED|NEEDS-PACKAGE-UPGRADE`

### Step 8 — Report unresolvable deps & package workspace

For each `needs-package-upgrade` dep, offer: Provide version / Set up package workspace / Drop.

**Package workspace setup:** Add git submodule at `packages/<short-name>/`. Branch selection: use the branch from `oldConstraint` (e.g., `dev-ticket/OMC-1880-6.1` → branch `ticket/OMC-1880-6.1`). If version constraint, pick branch with highest Oro version ≤ target. Create `feature/upgrade-7.0` from it. Add as path repo.

### Step 8b — Auto-upgrade extension composer.json

For each `packaged` dep:

**8b-1. Gather reference:** upstream `composer.json` (PHP constraint, require-dev), target Oro pattern (e.g., `7.0.*`), locked-versions map from `<newApp>/composer.lock`.

**8b-2. Upgrade each section:**
- `require`: `oro/*` → target pattern, `php` → upstream's constraint
- Third-party deps: if locked version satisfies → no change; if not → update to `^<major>.<minor>` from lock
- `require-dev`: `oro/*` → target, upstream-matching deps → upstream constraint

**8b-3.** Commit: `git -C packages/<name> add composer.json && git commit -m "Upgrade composer.json for Oro 7.0"`

**8b-4.** Probe: `<composer-flags> require --update-no-dev --no-install <package>:@dev`. On failure → apply Step 6c probing on failing third-party dep, update extension, re-commit, re-probe.

**8b-5.** Print per-package progress.

### Step 7 — Install resolved deps

All probing used `--no-install`. One final install downloads everything:

```bash
cd <newApp> && <composer-flags> install --no-dev --prefer-dist
```

No Bash timeout.

### Step 9 — Finalize

Update `.ai-upgrade/config.json` with `composer.status` (`complete`/`partial`/`baseline-established`) and `paths.packages`.

Report summary table: resolved, version-bumped, needs-package-upgrade, packaged, dropped.

### Step 9b — Commit dependency resolution

In `application/new/` — skip if nothing changed:
```bash
cd <newApp> && git add composer.json composer.lock && git diff --cached --quiet || \
  git commit -m "upgrade-composer: resolve dependencies for Oro 7.0"
```

Update state and refs in root:
```bash
git add .ai-upgrade/custom-deps.json .ai-upgrade/config.json .ai-upgrade/progress.json application/new packages/ 2>/dev/null; \
  git diff --cached --quiet || git commit -m "upgrade-composer: update state and submodule refs"
```

### Step 10 — Next step

- `packaged > 0` with unresolvable conflicts → user fixes `packages/<name>/composer.json`, re-run
- `needs-package-upgrade > 0` → set up workspaces first
- All resolved + patches → `/upgrade-patches`
- All resolved, no patches → `/upgrade-toolkit`

## `.ai-upgrade/custom-deps.json` Schema

```json
{
  "version": 1,
  "createdAt": "<ISO>",
  "updatedAt": "<ISO>",
  "upstreamTag": "7.0.0-rc",
  "dependencies": [{
    "name": "orolab/blog",
    "oldConstraint": "6.1.1",
    "newConstraint": null,
    "resolvedVersion": null,
    "repository": { "type": "vcs", "url": "...", "repoKey": "..." },
    "status": "pending",
    "classification": "orolab-extension",
    "packagePath": null,
    "notes": null
  }],
  "baselineDeps": ["php", "oro/crm-enterprise", "cweagans/composer-patches"]
}
```

**Status:** `pending` → `resolved` | `version-bumped` | `needs-package-upgrade` | `packaged` | `dropped`
