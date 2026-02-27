<?php

declare(strict_types=1);

namespace DT\Bundle\FormBundle\Form\Guesser;

use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\Form\Guess\ValueGuess;

abstract class AbstractEntityFieldsFormGuesser extends AbstractFormGuesser
{
    private ?array $fieldsConfig = null;

    abstract protected function createFieldsConfig(): array;

    abstract protected function getEntityClass(): string;

    /**
     * Required option is guessed by the separate guesser
     */
    #[\Override]
    public function guessRequired($class, $property): ?ValueGuess
    {
        if ($class !== $this->getEntityClass()) {
            return $this->createDefaultRequiredGuess();
        }

        $fieldConfig = $this->getPropertyFieldConfig($property);
        if (!$fieldConfig) {
            return $this->createDefaultRequiredGuess();
        }

        $options = $fieldConfig['options'] ?? [];
        if (!array_key_exists('required', $options)) {
            return $this->createDefaultRequiredGuess();
        }

        return new ValueGuess($options['required'], ValueGuess::HIGH_CONFIDENCE);
    }

    private function createDefaultRequiredGuess(): ValueGuess
    {
        return new ValueGuess(false, ValueGuess::LOW_CONFIDENCE);
    }

    /**
     * {@inheritdoc}
     */
    public function guessType($class, $property): ?TypeGuess
    {
        if ($class !== $this->getEntityClass()) {
            return $this->createDefaultTypeGuess();
        }

        $fieldConfig = $this->getPropertyFieldConfig($property);
        if (!$fieldConfig) {
            return $this->createDefaultTypeGuess();
        }

        $type = $fieldConfig['type'];
        $options = $fieldConfig['options'] ?? [];
        $options = $this->addLabelOption($options, $this->getEntityClass(), $property);

        return $this->createTypeGuess($type, $options);
    }

    protected function getPropertyFieldConfig(string $property): ?array
    {
        if (null === $this->fieldsConfig) {
            $this->fieldsConfig = $this->createFieldsConfig();
        }

        return $this->fieldsConfig[$property] ?? null;
    }
}
