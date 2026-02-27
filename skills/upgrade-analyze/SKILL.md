---
name: upgrade-analyze
description: "Vendor diff analysis, BC-breaks report, automatic signature fixes with verification. Uses semadiff commands from oro/upgrade-toolkit."
disable-model-invocation: false
allowed-tools: Bash, Read, Write, Edit, Glob, Grep, AskUserQuestion
---

# /upgrade-analyze — Vendor diff, BC-breaks & autofix

Invoke after `/upgrade-toolkit`.

## Progress Tracking

Update `.ai-upgrade/progress.json`: set `"in-progress"` on entry, `"completed"` on success, `"failed"`/`"blocked"` on error.

## Procedure

### Step 1 — Read config & identify targets

1. Read `.ai-upgrade/config.json` — extract `paths.currentApp`, `paths.newApp`, `paths.aiUpgradeRoot`, `paths.packages`.
2. Detect PHP runner: `which symfony` → `symfony php` or bare `php`.
3. Collect package workspaces from `.ai-upgrade/custom-deps.json` (non-null `packagePath`).
4. Verify both `<currentApp>/vendor/autoload.php` and `<newApp>/vendor/autoload.php` exist.

### Step 1b — Git Preamble

Ensure workspace root is a git repo: `git rev-parse --git-dir 2>/dev/null`. If not → `git init`, create `.gitignore` (`.ai-upgrade/*.sql.gz`, `application/*/vendor/`, `application/*/node_modules/`, `application/*/var/`, `*.sql`, `*.sql.gz`), initial commit.

### Step 2 — Verify semadiff binaries

Bundled with `oro/upgrade-toolkit`. Check: `ls <newApp>/bin/semadiff <newApp>/bin/report <newApp>/bin/find-dependents`. Missing → stop, run `/upgrade-toolkit` first.

### Step 3 — Classify vendor changes

```bash
cd <newApp> && <php-cmd> bin/semadiff \
  --before=<currentApp>/vendor --after=<newApp>/vendor \
  --output=<aiUpgradeRoot>/.ai-upgrade/semadiff-output \
  --exclude="*\Tests\*,*\Fixtures\*,*\TestFramework\*" 2>&1
```

**Timeout: 600000ms.** Outputs: `logic_changes.txt`, `signature_only.txt`, `cosmetic_only.txt`, `deleted_files.txt`, `new_files.txt`, `moved_files.txt`, `summary_report.txt`.

### Step 4 — Run `report` for main app

```bash
cd <newApp> && <php-cmd> bin/report \
  --before=<currentApp>/vendor --after=<newApp>/vendor \
  --dir=<newApp>/src --output=<aiUpgradeRoot>/.ai-upgrade/report.md \
  --exclude="*\Tests\*,*\Fixtures\*,*\TestFramework\*" 2>&1
```

**Timeout: 600000ms.** Generates:
- `.ai-upgrade/report.md` — logic changes with dependents + vendor diffs
- `.ai-upgrade/bc-breaks.md` — BC-breaking changes with usage analysis per class

### Step 5 — Find dependents in packages

Skip if no packages have PHP code.

```bash
cat .ai-upgrade/semadiff-output/{logic_changes,signature_only,deleted_files}.txt \
  | sort -u > .ai-upgrade/semadiff-output/breaking_changes.txt
```

For each package:
```bash
cd <newApp> && <php-cmd> bin/find-dependents \
  --dir=<package-php-root> \
  --fqcn-file=<aiUpgradeRoot>/.ai-upgrade/semadiff-output/breaking_changes.txt \
  --output=<aiUpgradeRoot>/.ai-upgrade/dependents-<name> \
  --type=extends,implements,traits \
  --exclude="*\Tests\*,*\Fixtures\*,*\TestFramework\*" 2>&1
```

### Step 6 — Decorator cross-reference

Grep `decorates:` in YAML across `<newApp>/src/` and packages. Cross-reference with `.ai-upgrade/container-dump.json`. Flag as CRITICAL any decorator whose decorated service FQCN appears in BC-breaks.

### Step 7 — Report analysis summary

Print: vendor change counts, package dependents, CRITICAL decorators, unresolved BC item count.

If zero unresolved items → skip to Step 11.

### Step 8 — Autofix BC-breaking changes

Parse `.ai-upgrade/bc-breaks.md`. Each vendor FQCN section contains:
- **Detail strings** — what changed (constructor, method signature, removal, etc.)
- **Resolution items** — `- [ ]` unresolved, `- [x]` resolved. Each has: dependent FQCN, usage type, method name, optional `paramDiff`

Process only `- [ ]` items. For each affected project class, **always read both** the project file and new vendor file before making changes.

**Method overrides:** match project method signature to new vendor parent. Update `parent::method()` args if params changed. Keep body unchanged.

**Constructor overrides:** update parent params + `parent::__construct()` args. Preserve project's own added DI parameters.

**Interface implementations:** update signatures. Stub new required methods with `throw new \RuntimeException('Not implemented')`.

**Moved/renamed classes:** read `moved_files.txt` (`Old\Fqcn -> New\Fqcn`), update `use` statements and inline references.

**Deleted classes:** add `// TODO: vendor class deleted in 7.0` comment — do NOT auto-fix.

**Skip (flag for manual review):** class/method made `final`, complex constructor DI changes, decorator coupling, trait conflicts.

### Step 9 — Verify fixes

```bash
cd <newApp> && rm -rf var/cache/prod/
cd <newApp> && ORO_ENV=prod <php-cmd> bin/console oro:entity-extend:cache:check 2>&1
cd <newApp> && ORO_ENV=prod <php-cmd> bin/console cache:warmup 2>&1
```

**5-min Bash timeout** per command. Both pass → Step 10.

**On failure:** read failing source + new vendor file, fix, grep all occurrences across `src/` and `packages/`, clear cache, re-run. Max 10 iterations, 2 attempts per error — then log as unfixable.

### Step 10 — Save results

Write `.ai-upgrade/autofix-results.json` with `autoFixed`, `manualReview`, `verification` (passed, iterations, errorsFixed, errorsRemaining), and `summary` counts.

### Step 11 — Commit

In `application/new/` — skip if clean:
```bash
cd <newApp> && git add -A src/ && git diff --cached --quiet || \
  git commit -m "upgrade-analyze: auto-fix BC-breaking signature changes"
```

Each package — skip if clean:
```bash
git -C packages/<name> add -A && git -C packages/<name> diff --cached --quiet || \
  git -C packages/<name> commit -m "upgrade-analyze: auto-fix BC-breaking signature changes"
```

Root:
```bash
git add .ai-upgrade/ application/new packages/ 2>/dev/null; \
  git diff --cached --quiet || git commit -m "upgrade-analyze: save reports and submodule refs"
```

### Step 12 — Summary & next steps

Print: reports generated, verification pass/fail, manual review count with file list.

```
For detailed vendor class history:
  cd <newApp> && <php-cmd> bin/class-diff --fqcn='...' --before=<currentApp>/vendor --after=<newApp>/vendor
```

Next: `/upgrade-test`.
