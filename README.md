# The Oro Source Code Upgrade Toolkit

A command line helper that facilitates upgrading the source code of the Oro application:
 * From version 5.0 to 5.1
 * From version 5.1 to 6.0
 * From version 5.0 to 6.0

Installation
------------

```bash
composer require oro/upgrade-toolkit:dev-master --dev
```

Suggested workflow
------------------

1. Analyze your code with and review suggested changes:

```bash
php bin/upgrade-toolkit --dry-run
```

2. Apply suggested changes:

```bash
php bin/upgrade-toolkit --clear-cache
```

3. Fix Code Style. Use IDE build-in solutions (E.G.: “Code > Reformat Code” in PhpStorm)

    or run Php-CS-Fixer
   
```bash
php bin/php-cs-fixer fix src --verbose --config=vendor/oro/platform/build/.php-cs-fixer.php
```

and PHP_CodeSniffer

```bash
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

If additional adjustments are needed - run the command with the `--help` option to get details

```bash
php bin/upgrade-toolkit --help
```
-------
Rector rule sets are still available to run separately:
```bash
bin/rector process src --config vendor/oro/upgrade-toolkit/sets/oro-51.php
```

Replace src with the path to your source directory, if not src/, and oro-51.php with the desired upgrade set (oro-51.php or oro-60.php).

You can add --dry-run to the bin/rector command to verify the results without actually making any changes.

Testing
-------

To run tests

```bash
php bin/phpunit --testsuite upgrade-toolkit --configuration vendor/oro/upgrade-toolkit/phpunit.xml.dist
```

License
-------

This bundle is under the MIT license. See the complete license [in the bundle](LICENSE).
