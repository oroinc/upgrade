<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\Oro70\OpenSpout\ReplaceWriterFactoryWithDirectInstantiationRector\Fixture;

use Box\Spout\Writer\Common\Creator\WriterFactory;

final class NoChangeIfOtherMethod
{
    public function run(): void
    {
        $writer = WriterFactory::createFromFile('file.xlsx');
    }
}
