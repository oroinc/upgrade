<?php

declare(strict_types=1);

namespace DT\Bundle\SetupBundle\Migrations\Schema\Archive\v1_7_3;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\OutdatedExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddLeadFields implements Migration, OutdatedExtendExtensionAwareInterface
{
    use \Oro\Bundle\EntityExtendBundle\Migration\Extension\OutdatedExtendExtensionAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        if ($schema->hasTable('orocrm_sales_lead')) {
            $table = $schema->getTable('orocrm_sales_lead');
            $table->addColumn(
                LeadFields::DT_SALESFORCE_ID,
                Types::STRING,
                [
                    'notnull' => false,
                    'length' => 18,
                ],
            );
            $this->outdatedExtendExtension->addEnumField(
                $schema,
                $table,
                LeadFields::DT_TYPE,
                ReservedEnumCodes::DT_LEAD_TYPE,
                false,
                false,
                ['extend' => ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM]],
            );
            $this->outdatedExtendExtension->addManyToOneRelation(
                $schema,
                $table,
                LeadFields::DT_REGION,
                'oro_dictionary_region',
                'name',
                ['extend' => ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM]],
            );
        }
    }
}
