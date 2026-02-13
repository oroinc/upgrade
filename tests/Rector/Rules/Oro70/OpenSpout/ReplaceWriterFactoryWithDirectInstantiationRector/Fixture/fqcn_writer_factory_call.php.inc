<?php

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\Oro70\OpenSpout\ReplaceWriterFactoryWithDirectInstantiationRector\Fixture;

use Box\Spout\Common\Type;

final class FqcnWriterFactoryCall
{
    public function run(): void
    {
        $writer = \Box\Spout\Writer\Common\Creator\WriterFactory::createFromType(Type::ODS);
    }
}

-----
<?php

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\Oro70\OpenSpout\ReplaceWriterFactoryWithDirectInstantiationRector\Fixture;

use Box\Spout\Common\Type;

final class FqcnWriterFactoryCall
{
    public function run(): void
    {
        $writer = new \OpenSpout\Writer\ODS\Writer();
    }
}
