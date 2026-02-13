<?php

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\Oro70\OpenSpout\ReplaceWriterFactoryWithDirectInstantiationRector\Fixture;

use Box\Spout\Writer\Common\Creator\WriterFactory;

final class NoChangeIfUnknownStringFormat
{
    public function run(): void
    {
        $writer = WriterFactory::createFromType('pdf');
    }
}
