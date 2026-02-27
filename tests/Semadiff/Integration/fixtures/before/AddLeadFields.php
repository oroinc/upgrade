<?php

declare(strict_types=1);

namespace DT\Bundle\SetupBundle\Migrations\Schema\Archive\v1_7_3;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddLeadFields implements Migration, ExtendExtensionAwareInterface
{
    /** @var ExtendExtension */
    protected ExtendExtension $extendExtension;

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

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
            $this->extendExtension->addEnumField(
                $schema,
                $table,
                LeadFields::DT_TYPE,
                ReservedEnumCodes::DT_LEAD_TYPE,
                false,
                false,
                ['extend' => ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM]],
            );
            $this->extendExtension->addManyToOneRelation(
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
