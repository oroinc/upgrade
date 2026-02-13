<?php

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\Oro70\OpenSpout\ReplaceReaderFactoryWithDirectInstantiationRector\Fixture;

use Box\Spout\Common\Type;

final class FqcnReaderFactoryCall
{
    public function run(): void
    {
        $reader = \Box\Spout\Reader\Common\Creator\ReaderFactory::createFromType(Type::ODS);
    }
}

-----
<?php

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\Oro70\OpenSpout\ReplaceReaderFactoryWithDirectInstantiationRector\Fixture;

use Box\Spout\Common\Type;

final class FqcnReaderFactoryCall
{
    public function run(): void
    {
        $reader = new \OpenSpout\Reader\ODS\Reader();
    }
}
