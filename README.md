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

Usage
-----

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
