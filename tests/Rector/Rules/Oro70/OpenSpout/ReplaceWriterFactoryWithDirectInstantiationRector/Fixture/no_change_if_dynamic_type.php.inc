<?php

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\Oro70\OpenSpout\ReplaceWriterFactoryWithDirectInstantiationRector\Fixture;

use Box\Spout\Writer\Common\Creator\WriterFactory;

final class NoChangeIfDynamicType
{
    public function run(string $type): void
    {
        $writer = WriterFactory::createFromType($type);
    }
}
