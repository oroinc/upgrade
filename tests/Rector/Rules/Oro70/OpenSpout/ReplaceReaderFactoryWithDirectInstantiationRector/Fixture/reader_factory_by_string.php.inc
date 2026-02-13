<?php

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\Oro70\OpenSpout\ReplaceReaderFactoryWithDirectInstantiationRector\Fixture;

use Box\Spout\Reader\Common\Creator\ReaderFactory;

final class ReaderFactoryByString
{
    public function run(): void
    {
        $reader = ReaderFactory::createFromType('csv');
    }
}

-----
<?php

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\Oro70\OpenSpout\ReplaceReaderFactoryWithDirectInstantiationRector\Fixture;

use Box\Spout\Reader\Common\Creator\ReaderFactory;

final class ReaderFactoryByString
{
    public function run(): void
    {
        $reader = new \OpenSpout\Reader\CSV\Reader();
    }
}
