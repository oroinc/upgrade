<?php

declare(strict_types=1);

namespace DT\Bundle\JdeFileIntegrationBundle\Feature\CategoryCode\Import\Type;

/**
 * Base class for category code import types.
 */
abstract class AbstractCategoryCodeType implements ImportTypeInterface
{
    public function __construct(
        private CategoryCodeProcessor $importProcessor,
        private ConfigManager $configManager,
    ) {
    }

    #[\Override]
    public function getLabel(): string
    {
        return $this->configManager->get($this->getConfigKey());
    }

    #[\Override]
    public function getImportProcessor(): ImportProcessorInterface
    {
        return $this->importProcessor;
    }

    /**
     * Return entity class for the code
     */
    #[\Override]
    abstract public function getEntityClass(): string;

    abstract protected function getConfigKey(): string;

    #[\Override]
    public function getImportJobName(): string
    {
        return 'dt_category_code_import';
    }

    #[\Override]
    public function getTemplateEntityClass(): string
    {
        return $this->getEntityClass();
    }
}
