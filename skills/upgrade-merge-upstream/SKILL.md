---
name: upgrade-merge-upstream
description: "Merge upstream Oro tag into application/new/. Adds Oro remote, fetches tags, merges target version with --allow-unrelated-histories. Categorizes merge conflicts by area."
disable-model-invocation: false
allowed-tools: Bash, Read, Write, Glob, Grep, AskUserQuestion
---

# /upgrade-merge-upstream — Merge Upstream Oro Tag

This skill adds the Oro remote, fetches tags, merges the target version tag into `application/new/`, and categorizes any merge conflicts. All operations happen in the `application/new/` submodule.

The user invokes `/upgrade-merge-upstream` after `/upgrade-setup`.

## Progress Tracking

Update `.ai-upgrade/progress.json`: set `"in-progress"` on entry with `startedAt`, `"completed"` on success with `completedAt` and summary in `notes`, `"blocked"` if merge conflicts remain.

## Procedure

### Step 1 — Read config

Read `.ai-upgrade/config.json` from the workspace root (the current working directory). Parse the JSON and extract:

- `paths.newApp` — absolute path to `application/new/` (the upgrade target)
- `oroUpstreamRepo` — the Oro GitHub remote URL
- `targetVersion` — the target Oro version tag (e.g., `7.0.0-rc`)

If the config file does not exist, tell the user to run `/upgrade-init` first and stop.

### Step 1b — Git Preamble

Ensure workspace root is a git repo: `git rev-parse --git-dir 2>/dev/null`. If not → `git init`, create `.gitignore` (`.ai-upgrade/*.sql.gz`, `application/*/vendor/`, `application/*/node_modules/`, `application/*/var/`, `*.sql`, `*.sql.gz`), initial commit.

### Step 2 — Ensure the Oro remote exists

In the **`application/new/`** directory, check if the `oro` remote already exists:

- **Missing** → add it pointing to `oroUpstreamRepo`.
- **Wrong URL** → warn the user and offer to update it. If declined, continue with existing URL.
- **Correct** → continue.

### Step 3 — Fetch tags from Oro remote

```bash
cd <newApp> && git fetch oro --tags
```

If the fetch fails (e.g., authentication error, network issue), report the error and stop. Suggest the user check their SSH keys or network connectivity.

### Step 4 — Verify the target tag exists

```bash
cd <newApp> && git tag -l "<targetVersion>"
```

If the tag is **not found** (empty output):

1. Extract the major.minor version from `targetVersion` (e.g., `7.0` from `7.0.0-rc`).
2. List available tags matching that major.minor:
   ```bash
   cd <newApp> && git tag -l "<major>.<minor>.*" | sort -V
   ```
3. Present the available tags to the user and ask them to pick one using AskUserQuestion.
4. Use the selected tag as the new `targetVersion` for the remaining steps.

If the tag **is found**, confirm it and continue.

### Step 5 — Merge the target tag

```bash
cd <newApp> && git merge <targetVersion> --allow-unrelated-histories
```

After the merge command completes, analyze the result:

**If the merge succeeded with no conflicts:** continue to Step 6.

**If the merge has conflicts:**
- Run `cd <newApp> && git diff --name-only --diff-filter=U` to get the list of conflicted files.
- Categorize the conflicts by area:

| Area | Matching Pattern |
|------|-----------------|
| Dependency conflicts (Composer) | `composer.json`, `composer.lock` |
| Node dependency conflicts | `package.json` |
| Configuration conflicts | Files under `config/` |
| Kernel registration conflicts | `src/AppKernel.php` |
| Custom code conflicts | Other files under `src/` |
| Other | Everything else |

- Display a summary table of conflicts grouped by area, then list each file.

### Step 6 — Clear old caches

```bash
cd <newApp> && rm -rf var/cache/prod/
```

### Step 7 — Commit

**Clean merge:** the merge commit was already created by `git merge`. Update root refs:
```bash
git add .ai-upgrade/progress.json application/new && git diff --cached --quiet || git commit -m "Update submodule refs after upstream merge"
```

**Conflicts:** after user resolves and commits in `application/new/`, run the same root ref update above.

### Step 8 — Report summary and next steps

Display: target tag, merge result (clean / N conflicts), cache cleared.

**If there are merge conflicts:** instruct the user to resolve all conflicts — especially `composer.json` (keep both sides), `config/`, `src/AppKernel.php` — then `git add . && git commit`. After their commit, update root submodule refs (Step 7). Next: `/upgrade-composer`.

**If the merge was clean:** next: `/upgrade-composer`.
