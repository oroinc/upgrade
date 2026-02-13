<?php

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\Oro70\OpenSpout\ReplaceWriterFactoryWithDirectInstantiationRector\Fixture;

use Box\Spout\Writer\Common\Creator\WriterFactory;

final class WriterFactoryByString
{
    public function run(): void
    {
        $writer = WriterFactory::createFromType('csv');
    }
}

-----
<?php

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\Oro70\OpenSpout\ReplaceWriterFactoryWithDirectInstantiationRector\Fixture;

use Box\Spout\Writer\Common\Creator\WriterFactory;

final class WriterFactoryByString
{
    public function run(): void
    {
        $writer = new \OpenSpout\Writer\CSV\Writer();
    }
}
