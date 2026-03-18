# The Oro Source Code Upgrade Toolkit

A command line tool that simplifies upgrading Oro application source code from versions 4.0, 4.1, 4.2, 5.0, 5.1, and 6.0 to version 6.1.

AI-Assisted Upgrade
--------------------

This repository includes [Claude Code skills](https://github.com/vercel-labs/skills) for AI-assisted Oro application upgrade.

Start by creating an empty directory that will serve as the upgrade meta-repository, then install the skills there:

```bash
mkdir my-upgrade && cd my-upgrade
npx skills add https://github.com/oroinc/upgrade/tree/experimental/skills --all
```

By default, the orchestrator (`/upgrade-all`) collects all required input upfront, then runs every step autonomously — committing progress after each one — without further prompts. You can change this behavior (e.g., require confirmation between steps) by editing `AGENTS.md` in the upgrade directory.

### Workspace structure

The skills build up the following meta-repository layout:

```
my-upgrade/                        # AI upgrade workspace root
├── .ai-upgrade/                   # State & artifacts (gitignored)
│   ├── config.json                # Workspace configuration (editions, paths, versions)
│   ├── answers.json               # Pre-collected user decisions
│   ├── progress.json              # Step-by-step execution tracking
│   └── ...                        # Reports, diffs, build state
├── application/
│   ├── current/                   # Submodule: existing app on your base branch
│   └── new/                       # Submodule: upgrade target (feature/upgrade-7.0)
├── packages/                      # Package workspaces for unresolvable dependencies
├── changelogs/                    # Downloaded & trimmed changelogs per vendor
└── .gitignore
```

Each application instance runs in isolated Docker containers with separate databases. The `/upgrade-init` skill clones your repository as submodules, detects the app edition, PHP versions, and config style, then writes `.ai-upgrade/config.json` used by all subsequent steps.

### Available skills

The upgrade skills guide you through the full process — from initialization and merge to build fixes and testing. Run `/upgrade-all` to start the orchestrated workflow, or invoke individual steps:

`upgrade-start` · `upgrade-init` · `upgrade-setup` · `upgrade-theme-migration` · `upgrade-merge-upstream` · `upgrade-composer` · `upgrade-changelogs` · `upgrade-patches` · `upgrade-toolkit` · `upgrade-build` · `upgrade-analyze` · `upgrade-test`

Manual Usage
------------

The upgrade toolkit can also be used manually as a standalone Composer package, without the AI-assisted workflow.

### Installation

```bash
composer require oro/upgrade-toolkit:dev-master --dev
```

### Suggested Workflow

1. Analyze your code with and review suggested changes:

```bash
php bin/upgrade-toolkit --dry-run
```

2. Apply suggested changes:

```bash
php bin/upgrade-toolkit
```

3. Fix Code Style. Use IDE build-in solutions (e.g., “Code > Reformat Code” in PhpStorm), or run Php-CS-Fixer and PHP_CodeSniffer
   
```bash
# Run Php-CS-Fixer 
php bin/php-cs-fixer fix src --verbose --config=vendor/oro/platform/build/.php-cs-fixer.php

# Run PHP_CodeSniffer
php bin/phpcbf src/ -p --encoding=utf-8 --extensions=php --standard=vendor/oro/platform/build/Oro/phpcs.xml
```

4. Run required [automated  tests](https://doc.oroinc.com/backend/automated-tests/) to ensure that the upgraded code still works properly.


Usage
-----

Run:
```bash
php bin/upgrade-toolkit
```
In most cases, the command can be used without any options.

If additional adjustments are needed, run the command with the `--help` option.

```bash
php bin/upgrade-toolkit --help
```

You can run rector rule sets separately by executing the following command:

> **💡** The upgrade-toolkit wrapper ensures that pre- and post-processing capabilities are automatically executed, 
> maintaining data consistency and applying additional transformations required for the upgrade.

```bash
php bin/upgrade-toolkit rector process src --config vendor/oro/upgrade-toolkit/sets/oro-51.php
```

If your source directory is not src/, replace `src` with the path to it and update `oro-51.php` with the desired upgrade set (either `oro-51.php` or `oro-60.php`).

To verify the results without making any changes, add the `--dry-run` option to the `bin/rector` command.

You can also process the .yml files to run separately:
```bash
php bin/upgrade-toolkit yml:fix
```
In this case, the recommended workflow is as follows:

1. Check and verify results without making any changes first:
```bash
   php bin/upgrade-toolkit yml:fix --dry-run
```

2. Apply changes:
```bash
   php bin/upgrade-toolkit yml:fix
```

Testing
-------

To run tests, use the following command:

```bash
php bin/phpunit --testsuite upgrade-toolkit --configuration vendor/oro/upgrade-toolkit/phpunit.xml.dist
```

License
-------

This bundle is under the MIT license. See the complete license [in the bundle](LICENSE).
