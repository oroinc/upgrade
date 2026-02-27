---
name: upgrade-test
description: "Run test suites and correlate failures with vendor change TODOs. Groups failures as likely-related, possibly-related, or unrelated to the upgrade. Helps prioritize which test failures to fix first."
disable-model-invocation: false
allowed-tools: Bash, Read, Write, Glob, Grep, AskUserQuestion
---

# upgrade-test

Last skill in the upgrade workflow, invoked after `/upgrade-analyze`.

## Progress Tracking

Update `.ai-upgrade/progress.json`: set `"in-progress"` on entry with `startedAt`, `"completed"` on success with `completedAt` and summary in `notes`, `"failed"`/`"blocked"` on error.

## Pre-collected Answers

Check `.ai-upgrade/answers.json` before asking — use non-null values silently.

| Question | answers.json key | Value mapping |
|----------|-----------------|---------------|
| Test suite selection (Step 3) | `preferences.testSuites` | `["unit"]`, `["functional"]`, `["all"]`, or array of multiple |

## Procedure

### Step 1 — Resolve working directory

Read `.ai-upgrade/config.json` — use `newWorktreePath` or fall back to `projectRoot`. Store as `$WORKDIR`.

### Step 1b — Git Preamble

Ensure workspace root is a git repo: `git rev-parse --git-dir 2>/dev/null`. If not → `git init`, create `.gitignore` (`.ai-upgrade/*.sql.gz`, `application/*/vendor/`, `application/*/node_modules/`, `application/*/var/`, `*.sql`, `*.sql.gz`), initial commit.

### Step 2 — Detect test runner

The runner is always `bin/phpunit`. Read `phpunit.xml(.dist)` for available `<testsuite>` names.

### Step 3 — Ask which test suites to run

AskUserQuestion (multiSelect): Unit / Functional / Integration / All / Custom command.

Warn for functional/integration: "Requires a running database."

### Step 4 — Run tests

Execute from `$WORKDIR` with up to 600000ms timeout. PHPUnit exit 1 = failures (not a command error).

### Step 5 — Parse test output

Extract: summary counts (total, passed, failed, errored, skipped) and per-failure records (testClass, testMethod, type, message, file, line).

### Step 6 — Correlate failures with TODO list

If `.ai-upgrade/todos.json` exists, cross-reference:

- **Likely related**: failing test class/file, class under test, or error-mentioned class appears directly in a TODO item
- **Possibly related**: error mentions a namespace with TODO items, or failing test is in a bundle with TODOs
- **Unrelated**: no correlation found

### Step 7 — Display results

Test summary line, then correlated failures table (test, error, related TODO, correlation, severity), then uncorrelated failures table.

### Step 8 — Save results

Write `.ai-upgrade/test-results.json` with summary counts, correlated failures (with severity), and uncorrelated failures.

### Step 9 — Commit test results

Skip if nothing changed:
```bash
git add .ai-upgrade/test-results.json .ai-upgrade/progress.json && \
  git diff --cached --quiet || git commit -m "upgrade-test: save test results"
```

### Step 10 — Next steps

1. Fix CRITICAL-correlated failures first (definitively upgrade-caused)
2. Investigate possibly-related failures (check related TODOs)
3. Check unrelated failures against old branch (may be pre-existing)
4. Re-run `/upgrade-test` after fixes to verify progress
