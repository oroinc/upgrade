---
name: upgrade-start
description: "Collect all upfront-answerable upgrade decisions into .ai-upgrade/answers.json. Validates inputs, writes the file. Run before any other upgrade skill or let the orchestrator invoke it automatically."
disable-model-invocation: true
allowed-tools: Bash, Read, Write, Edit, Glob, Grep, AskUserQuestion
---

# /upgrade-start — Collect Upgrade Decisions Upfront

The AI upgrade root directory is always the **parent of `application/`**.

## Git Preamble

Ensure workspace root is a git repo: `git rev-parse --git-dir 2>/dev/null`. If not → `git init`, create `.gitignore` (`.ai-upgrade/*.sql.gz`, `application/*/vendor/`, `application/*/node_modules/`, `application/*/var/`, `*.sql`, `*.sql.gz`), initial commit.

## Step 1 — Check for Existing Answers

Read `.ai-upgrade/answers.json`. If exists: show summary, ask **Use as-is** or **Update**. If use-as-is → stop. If update → continue with existing values as defaults.

## Step 2 — Ask 3 Questions (1 AskUserQuestion call)

Single AskUserQuestion, 3 questions:

**2.1 Repository URL** — Git URL (HTTPS or SSH format)
**2.2 Base Branch** — Options: master, main, develop, Other
**2.3 Database Dump Path** — Options: Skip (no dump), Other (type path)

## Step 3 — Derive Install Method

- If dump path provided → `installMethod: "dump-restore"`
- If no dump (skipped) → `installMethod: "clean-install"`

## Step 4 — Set Defaults

Hardcoded preferences (no questions needed):
- `testSuites`: `["unit"]`
- `themeLayoutConsolidation`: `null` (detected at runtime by upgrade-theme-migration)
- `themeAssetDuplicates`: `"remove"`

Policy defaults (use recommended values):
- `onExistingState`: `"resume"`
- `onBranchConflict`: `"use-existing"`
- `onUnresolvableDep`: `"package-workspace"`
- `onBuildErrorMaxAttempts`: `"continue"`

## Step 5 — Validate Inputs

1. **Repository URL**: Convert HTTPS → SSH. Test: `git ls-remote <ssh-url> HEAD`. Warn on failure but save.
2. **Database dump path**: `test -f "<path>"`. Warn if not found but save.
3. **Branch name**: `git ls-remote --heads <ssh-url> <branch>`. Warn if not found but save.

## Step 6 — Write answers.json

```bash
mkdir -p .ai-upgrade
```

Write `.ai-upgrade/answers.json`:

```json
{
  "version": 1,
  "collectedAt": "<ISO 8601>",
  "inputs": {
    "repositoryUrl": "<SSH URL>",
    "baseBranch": "<branch>",
    "databaseDumpPath": "<path or null>"
  },
  "preferences": {
    "installMethod": "dump-restore|clean-install",
    "themeLayoutConsolidation": null,
    "themeAssetDuplicates": "remove",
    "testSuites": ["unit"]
  },
  "policies": {
    "onExistingState": "resume",
    "onBranchConflict": "use-existing",
    "onUnresolvableDep": "package-workspace",
    "onBuildErrorMaxAttempts": "continue"
  }
}
```

Install method is derived from dump path (see Step 3). All preferences and policies use fixed defaults (see Step 4). `themeLayoutConsolidation: null` = detected at runtime.

## Step 7 — Commit answers

```bash
git add .ai-upgrade/answers.json && git diff --cached --quiet || git commit -m "upgrade-start: collect upgrade decisions"
```

## Step 8 — Report Summary

Print all collected answers grouped by Inputs / Preferences / Policies. Next: `/upgrade` or `/upgrade-init`.
