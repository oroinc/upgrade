<?php

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class NotAMigration implements Installation
{
    use ExtendExtensionAwareTrait;

    private $extendExtension;

    public function setExtendExtension($extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    public function getMigrationVersion()
    {
        // Implement getMigrationVersion() method.
    }

    public function up(Schema $schema, QueryBag $queries)
    {
        // Implement up() method.
    }
}
-----
<?php

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class NotAMigration implements Installation
{
    use ExtendExtensionAwareTrait;

    private $extendExtension;

    public function setExtendExtension($extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    public function getMigrationVersion()
    {
        // Implement getMigrationVersion() method.
    }

    public function up(Schema $schema, QueryBag $queries)
    {
        // Implement up() method.
    }
}
