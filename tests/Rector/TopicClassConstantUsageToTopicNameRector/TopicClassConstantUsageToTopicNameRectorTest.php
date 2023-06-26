<?php

declare(strict_types=1);

namespace Oro\Tests\Rector\TopicClassConstantUsageToTopicNameRector;

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

class TopicClassConstantUsageToTopicNameRectorTest extends AbstractRectorTestCase
{
    /**
     * @dataProvider provideData
     */
    public function test(string $filePath): void
    {
        require_once __DIR__ . '/Mocks/Async/Topic/topic_classes.php';

        $this->doTestFile($filePath);
    }

    public static function provideData(): \Iterator
    {
        return self::yieldFilesFromDirectory(__DIR__ . '/Fixture');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/configured_rule.php';
    }
}
