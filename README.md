# The Oro Source Code Upgrade Toolkit

A command line tool that simplifies upgrading Oro application source code from versions 4.0, 4.1, 4.2, 5.0, 5.1, and 6.0 to version 6.1.

Installation
------------

```bash
composer require oro/upgrade-toolkit:dev-master --dev
```

Suggested Workflow
------------------

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

```bash
bin/rector process src --config vendor/oro/upgrade-toolkit/sets/oro-51.php
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
