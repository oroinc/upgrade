<?php

declare(strict_types=1);

namespace DT\Bundle\JdeFileIntegrationBundle\Feature\CategoryCode\Import\Type;

abstract class AbstractCategoryCodeType implements ImportTypeInterface
{
    public function __construct(private CategoryCodeProcessor $importProcessor, private ConfigManager $configManager)
    {
    }

    public function getLabel(): string
    {
        return $this->configManager->get($this->getConfigKey());
    }

    /**
     * @return ImportProcessorInterface
     */
    public function getImportProcessor(): ImportProcessorInterface
    {
        return $this->importProcessor;
    }

    /**
     * Return entity class for the code
     *
     * @return string
     */
    abstract public function getEntityClass(): string;

    /**
     * @return string
     */
    abstract protected function getConfigKey(): string;

    public function getImportJobName(): string
    {
        return 'dt_category_code_import';
    }

    /**
     * @return string
     */
    public function getTemplateEntityClass(): string
    {
        return $this->getEntityClass();
    }
}
