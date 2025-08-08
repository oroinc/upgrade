<?php

use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;

class EdgeCaseMigration implements Migration
{
    use ExtendExtensionAwareTrait;

    private $extendExtension;
    private ExtendExtension $outdatedExtendExtension;

    public function setExtendExtension($extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }
}
-----
<?php

use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;

class EdgeCaseMigration implements Migration
{
    use \Oro\Bundle\EntityExtendBundle\Migration\Extension\OutdatedExtendExtensionAwareTrait;
}
