<?php

use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;

class SomeMigration implements Migration
{
    use ExtendExtensionAwareTrait;

    private $extendExtension;

    public function setExtendExtension($extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }
}
-----
<?php

use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;

class SomeMigration implements Migration
{
    use \Oro\Bundle\EntityExtendBundle\Migration\Extension\OutdatedExtendExtensionAwareTrait;
}
