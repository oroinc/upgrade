---
name: upgrade-all
description: "Orchestrator — runs all upgrade steps sequentially. Reads progress.json to find current step, reads answers.json for pre-collected decisions, survives context compaction."
disable-model-invocation: true
allowed-tools: Bash, Read, Write, Edit, Glob, Grep, AskUserQuestion
---

# /upgrade-all — Orchestrator

Runs all upgrade steps sequentially. Tracks progress in `.ai-upgrade/progress.json`, uses pre-collected answers from `.ai-upgrade/answers.json`.

**CRITICAL — Compaction survival:** After compaction or confusion, re-read `progress.json` and `answers.json`. Find the `"in-progress"` step or first `"pending"` after last `"completed"`. Read that step's SKILL.md and continue. Do NOT rely on memory.

The AI upgrade root directory is always the **parent of `application/`**.

## Step Sequence

```
1. upgrade-init              7. upgrade-patches (skip if no patch plugin)
2. upgrade-setup             8. upgrade-toolkit
3. upgrade-theme-migration   9. upgrade-build
4. upgrade-merge-upstream   10. upgrade-analyze
5. upgrade-composer         11. upgrade-test
6. upgrade-changelogs
```

## Procedure

### Git Preamble

Ensure workspace root is a git repo: `git rev-parse --git-dir 2>/dev/null`. If not → `git init`, create `.gitignore` (`.ai-upgrade/*.sql.gz`, `application/*/vendor/`, `application/*/node_modules/`, `application/*/var/`, `*.sql`, `*.sql.gz`), initial commit.

### Step 1 — Initialize progress tracking

Read `.ai-upgrade/progress.json`. If missing, create with all 11 steps as `"pending"`.

### Step 2 — Ensure answers exist

Read `.ai-upgrade/answers.json`. If missing → read and execute `.claude/skills/upgrade-start/SKILL.md` inline.

### Step 3 — Determine current step

1. `"in-progress"` → resume that step (was interrupted)
2. `"blocked"` → report blockage, **STOP**
3. `"failed"` → report failure, **STOP** (user fixes and re-runs, or sets status to `"pending"` to retry)
4. Otherwise → first `"pending"` step in sequence

### Step 4 — Check skip conditions

- **`upgrade-patches`**: skip if `config.json` → `detection.hasPatchPlugin` is `false`
- **`upgrade-theme-migration`**: skip if `config.json` → `detection.hasCustomThemes` is explicitly `false`

Set status `"skipped"` with notes, loop back to Step 3.

### Step 5 — Execute current step

Read `.claude/skills/<step-name>/SKILL.md`, follow instructions. Step updates progress.json on entry/exit. After completion → loop to Step 3.

### Step 6 — Completion

When all steps are `"completed"` or `"skipped"`:

Final commit:
```bash
git add .ai-upgrade/progress.json && git diff --cached --quiet || git commit -m "upgrade-all: upgrade to Oro 7.0 complete"
```

```
=== Oro 7.0 Upgrade Complete ===

Step                     Status      Duration
-------------------------------------------------
<each step with status and time>

Artifacts:
  .ai-upgrade/config.json          — configuration
  .ai-upgrade/answers.json         — decisions
  .ai-upgrade/progress.json        — execution log
  changelogs/                      — trimmed changelogs per package
  .ai-upgrade/bc-breaks.md         — BC-breaking changes
  .ai-upgrade/report.md            — vendor change report
  .ai-upgrade/autofix-results.json — auto-fix summary
  .ai-upgrade/test-results.json    — test results
  application/new/manual-edits.md  — manual edits log
```

## Progress JSON

```json
{
  "version": 1,
  "lastUpdated": "ISO 8601",
  "steps": {
    "<step-name>": {
      "status": "pending|in-progress|completed|failed|skipped|blocked",
      "startedAt": null,
      "completedAt": null,
      "notes": "checkpoint info"
    }
  }
}
```

Transitions: `pending` → `in-progress` → `completed`, `pending` → `skipped`, `in-progress` → `failed`/`blocked`. The `notes` field survives compaction — tells resumed agent where the step left off.
