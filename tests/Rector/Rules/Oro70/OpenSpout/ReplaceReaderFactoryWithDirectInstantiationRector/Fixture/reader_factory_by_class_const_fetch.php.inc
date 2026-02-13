<?php

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\Oro70\OpenSpout\ReplaceReaderFactoryWithDirectInstantiationRector\Fixture;

use Box\Spout\Common\Type;
use Box\Spout\Reader\Common\Creator\ReaderFactory;

final class ReaderFactoryByClassConstFetch
{
    public function run(): void
    {
        $reader = ReaderFactory::createFromType(Type::XLSX);
    }
}

-----
<?php

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\Oro70\OpenSpout\ReplaceReaderFactoryWithDirectInstantiationRector\Fixture;

use Box\Spout\Common\Type;
use Box\Spout\Reader\Common\Creator\ReaderFactory;

final class ReaderFactoryByClassConstFetch
{
    public function run(): void
    {
        $reader = new \OpenSpout\Reader\XLSX\Reader();
    }
}
