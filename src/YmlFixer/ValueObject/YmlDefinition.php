<?php

namespace Oro\UpgradeToolkit\YmlFixer\ValueObject;

/**
 * Contains all .yml file data needed to process it
 */
final class YmlDefinition
{
    private bool $isUpdated = false;
    private ?string $updatedStringDefinition = null;
    private array $appliedRules = [];
    private array $errors = [];

    public function __construct(
        private ?string $filePath = null,
        private ?string $stringDefinition = null,
        private ?array $arrayDefinition = null,
    ) {
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): void
    {
        $this->filePath = $filePath;
    }

    public function getStringDefinition(): ?string
    {
        return $this->stringDefinition;
    }

    public function setStringDefinition(?string $stringDefinition): void
    {
        $this->stringDefinition = $stringDefinition;
    }

    public function getArrayDefinition(): ?array
    {
        return $this->arrayDefinition;
    }

    public function setArrayDefinition(?array $arrayDefinition): void
    {
        $this->arrayDefinition = $arrayDefinition;
    }

    public function getAppliedRules(): array
    {
        return $this->appliedRules;
    }

    public function setAppliedRule(string $rule): void
    {
        $this->appliedRules[] = $rule;
    }

    public function isUpdated(): bool
    {
        return $this->isUpdated;
    }

    public function updated(): void
    {
        $this->isUpdated = true;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function setError(\Throwable $error): void
    {
        $this->errors[] = $error;
    }

    public function getUpdatedStringDefinition(): ?string
    {
        return $this->updatedStringDefinition;
    }

    public function setUpdatedStringDefinition(?string $updatedStringDefinition): void
    {
        $this->updatedStringDefinition = $updatedStringDefinition;
    }
}
