<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Tests\Semadiff\Unit\Classifier;

use Oro\UpgradeToolkit\Semadiff\Classifier\ChangeClassifier;
use Oro\UpgradeToolkit\Semadiff\Comparator\FileComparisonResult;
use PHPUnit\Framework\TestCase;

final class ChangeClassifierTest extends TestCase
{
    private ChangeClassifier $classifier;

    protected function setUp(): void
    {
        $this->classifier = new ChangeClassifier();
    }

    public function testCosmeticWhenNothingChanged(): void
    {
        $result = new FileComparisonResult(
            classStructureChanged: false,
            signatureChanged: false,
            bodyChanged: false,
            membersAddedOrRemoved: false,
        );

        $this->assertSame(ChangeClassifier::COSMETIC, $this->classifier->classify($result));
    }

    public function testSignatureWhenOnlySignatureChanged(): void
    {
        $result = new FileComparisonResult(
            classStructureChanged: false,
            signatureChanged: true,
            bodyChanged: false,
            membersAddedOrRemoved: false,
        );

        $this->assertSame(ChangeClassifier::SIGNATURE, $this->classifier->classify($result));
    }

    public function testLogicWhenBodyChanged(): void
    {
        $result = new FileComparisonResult(
            classStructureChanged: false,
            signatureChanged: false,
            bodyChanged: true,
            membersAddedOrRemoved: false,
        );

        $this->assertSame(ChangeClassifier::LOGIC, $this->classifier->classify($result));
    }

    public function testLogicWhenClassStructureChanged(): void
    {
        $result = new FileComparisonResult(
            classStructureChanged: true,
            signatureChanged: false,
            bodyChanged: false,
            membersAddedOrRemoved: false,
        );

        $this->assertSame(ChangeClassifier::LOGIC, $this->classifier->classify($result));
    }

    public function testLogicWhenMembersAddedOrRemoved(): void
    {
        $result = new FileComparisonResult(
            classStructureChanged: false,
            signatureChanged: false,
            bodyChanged: false,
            membersAddedOrRemoved: true,
        );

        $this->assertSame(ChangeClassifier::LOGIC, $this->classifier->classify($result));
    }

    public function testLogicWinsOverSignature(): void
    {
        $result = new FileComparisonResult(
            classStructureChanged: false,
            signatureChanged: true,
            bodyChanged: true,
            membersAddedOrRemoved: false,
        );

        $this->assertSame(ChangeClassifier::LOGIC, $this->classifier->classify($result));
    }
}
