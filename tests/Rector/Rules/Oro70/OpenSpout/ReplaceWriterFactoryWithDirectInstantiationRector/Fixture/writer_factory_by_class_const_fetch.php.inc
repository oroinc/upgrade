<?php

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\Oro70\OpenSpout\ReplaceWriterFactoryWithDirectInstantiationRector\Fixture;

use Box\Spout\Common\Type;
use Box\Spout\Writer\Common\Creator\WriterFactory;

final class WriterFactoryByClassConstFetch
{
    public function run(): void
    {
        $writer = WriterFactory::createFromType(Type::XLSX);
    }
}

-----
<?php

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\Oro70\OpenSpout\ReplaceWriterFactoryWithDirectInstantiationRector\Fixture;

use Box\Spout\Common\Type;
use Box\Spout\Writer\Common\Creator\WriterFactory;

final class WriterFactoryByClassConstFetch
{
    public function run(): void
    {
        $writer = new \OpenSpout\Writer\XLSX\Writer();
    }
}
