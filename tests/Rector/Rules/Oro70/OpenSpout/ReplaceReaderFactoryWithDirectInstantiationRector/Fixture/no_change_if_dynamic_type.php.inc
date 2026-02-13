<?php

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\Oro70\OpenSpout\ReplaceReaderFactoryWithDirectInstantiationRector\Fixture;

use Box\Spout\Reader\Common\Creator\ReaderFactory;

final class NoChangeIfDynamicType
{
    public function run(string $type): void
    {
        $reader = ReaderFactory::createFromType($type);
    }
}
