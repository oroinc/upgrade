<?php

declare(strict_types=1);

namespace Oro\Rector\Testing\PHPUnit;

use Oro\Rector\Application\ApplicationFileProcessor;
use PHPUnit\Framework\ExpectationFailedException;
use Rector\Configuration\ConfigurationFactory;
use Rector\Configuration\Option;
use Rector\Configuration\Parameter\SimpleParameterProvider;
use Rector\Exception\ShouldNotHappenException;
use Rector\NodeTypeResolver\Reflection\BetterReflection\SourceLocatorProvider\DynamicSourceLocatorProvider;
use Rector\Testing\Fixture\FixtureFileUpdater;
use Rector\Testing\Fixture\FixtureSplitter;
use Rector\Testing\PHPUnit\ValueObject\RectorTestResult;
use RectorPrefix202403\Nette\Utils\FileSystem;
use RectorPrefix202403\Nette\Utils\Strings;

/**
 * Modification of
 * @see \Rector\Testing\PHPUnit\AbstractRectorTestCase
 *
 * Added file deletion and adding handling
 *
 * Copyright (c) 2017-present Tomáš Votruba (https://tomasvotruba.cz)
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 */
abstract class AbstractRectorTestCase extends \Rector\Testing\PHPUnit\AbstractRectorTestCase
{
    private DynamicSourceLocatorProvider $dynamicSourceLocatorProvider;
    private ApplicationFileProcessor $applicationFileProcessor;
    private ?string $inputFilePath = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->applicationFileProcessor = $this->make(ApplicationFileProcessor::class);
        $this->dynamicSourceLocatorProvider = $this->make(DynamicSourceLocatorProvider::class);
    }

    protected function tearDown(): void
    {
        // Clear temporary file
        if (\is_string($this->inputFilePath)) {
            FileSystem::delete($this->inputFilePath);
        }
    }

    protected function doTestFile(string $fixtureFilePath): void
    {
        // prepare input file contents and expected file output contents
        $fixtureFileContents = FileSystem::read($fixtureFilePath);
        if (FixtureSplitter::containsSplit($fixtureFileContents)) {
            // changed content
            [$inputFileContents, $expectedFileContents] = FixtureSplitter::splitFixtureFileContents($fixtureFileContents);
        } else {
            // no change
            $inputFileContents = $fixtureFileContents;
            $expectedFileContents = $fixtureFileContents;
        }
        $inputFilePath = $this->createInputFilePath($fixtureFilePath);
        // to remove later in tearDown()
        $this->inputFilePath = $inputFilePath;
        if ($fixtureFilePath === $inputFilePath) {
            throw new ShouldNotHappenException('Fixture file and input file cannot be the same: ' . $fixtureFilePath);
        }
        // write temp file
        FileSystem::write($inputFilePath, $inputFileContents, null);
        $this->doTestFileMatchesExpectedContent($inputFilePath, $inputFileContents, $expectedFileContents, $fixtureFilePath);
    }

    private function doTestFileMatchesExpectedContent(string $originalFilePath, string $inputFileContents, string $expectedFileContents, string $fixtureFilePath): void
    {
        SimpleParameterProvider::setParameter(Option::SOURCE, [$originalFilePath]);
        // the file is now changed (if any rule matches)
        $rectorTestResult = $this->processFilePath($originalFilePath);
        $changedContents = $rectorTestResult->getChangedContents();
        $fixtureFilename = \basename($fixtureFilePath);
        $failureMessage = \sprintf('Failed on fixture file "%s"', $fixtureFilename);
        // give more context about used rules in case of set testing
        if (\count($rectorTestResult->getAppliedRectorClasses()) > 1) {
            $failureMessage .= \PHP_EOL . \PHP_EOL;
            $failureMessage .= 'Applied Rector rules:' . \PHP_EOL;
            foreach ($rectorTestResult->getAppliedRectorClasses() as $appliedRectorClass) {
                $failureMessage .= ' * ' . $appliedRectorClass . \PHP_EOL;
            }
        }
        try {
            $this->assertSame($expectedFileContents, $changedContents, $failureMessage);
        } catch (ExpectationFailedException $exception) {
            FixtureFileUpdater::updateFixtureContent($inputFileContents, $changedContents, $fixtureFilePath);
            // if not exact match, check the regex version (useful for generated hashes/uuids in the code)
            $this->assertStringMatchesFormat($expectedFileContents, $changedContents, $failureMessage);
        }
    }

    private function createInputFilePath(string $fixtureFilePath): string
    {
        $inputFileDirectory = \dirname($fixtureFilePath);
        // remove ".inc" suffix
        if (\substr_compare($fixtureFilePath, '.inc', -\strlen('.inc')) === 0) {
            $trimmedFixtureFilePath = Strings::substring($fixtureFilePath, 0, -4);
        } else {
            $trimmedFixtureFilePath = $fixtureFilePath;
        }
        $fixtureBasename = \pathinfo($trimmedFixtureFilePath, \PATHINFO_BASENAME);
        return $inputFileDirectory . '/' . $fixtureBasename;
    }

    private function processFilePath(string $filePath): RectorTestResult
    {
        $this->dynamicSourceLocatorProvider->setFilePath($filePath);
        /** @var ConfigurationFactory $configurationFactory */
        $configurationFactory = $this->make(ConfigurationFactory::class);
        $configuration = $configurationFactory->createForTests([$filePath]);
        $processResult = $this->applicationFileProcessor->processFiles([$filePath], $configuration);
        // Process files that should be deleted
        $this->applicationFileProcessor->deletedFilesProcessor->process($configuration);
        $this->applicationFileProcessor->deletedFilesProcessor->deleteTmpFile();
        // Process files that should be added
        $this->applicationFileProcessor->addedFilesProcessor->process($configuration);
        $this->applicationFileProcessor->addedFilesProcessor->deleteTmpFile();
        // return changed file contents
        $changedFileContents = is_file($filePath) ? FileSystem::read($filePath) : '';
        return new RectorTestResult($changedFileContents, $processResult);
    }
}
