# The Oro Source Code Upgrade Toolkit

A command line helper to upgrade the Oro application's source code from v5.0 to v5.1.

Installation
------------

```bash
composer require oro/upgrade-toolkit:dev-master --dev
```

Usage
-----

```bash
bin/rector process src --config vendor/oro/upgrade-toolkit/config/set/oro-51.php
```

(Replace src with the path to your source directory, if not src/.)

You can add --dry-run to the bin/rector command to verify the results without actually making any changes.

Advanced Configuration
----------------------
To include additional Rector rules, or customize which files/directories should be processed,  give your project a rector.php file.

The following example runs the Oro 5.1 rule set:

```php
<?php

use Oro\Rector\OroSetList;
use Rector\Config\RectorConfig;

return static function(RectorConfig $rectorConfig): void {
    // Import the Oro 5.1 upgrade rule set
    $rectorConfig->sets([
        OroSetList::ORO_51
    ]);
};
```

License
-------

This bundle is under the MIT license. See the complete license [in the bundle](LICENSE).
