<?php

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\Oro70\OpenSpout\ReplaceReaderFactoryWithDirectInstantiationRector\Fixture;

use Box\Spout\Reader\Common\Creator\ReaderFactory;

final class NoChangeIfUnknownStringFormat
{
    public function run(): void
    {
        $reader = ReaderFactory::createFromType('pdf');
    }
}
