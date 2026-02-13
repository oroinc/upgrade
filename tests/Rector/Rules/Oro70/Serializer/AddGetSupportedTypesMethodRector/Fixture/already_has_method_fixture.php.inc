<?php

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class SomeNormalizer implements NormalizerInterface
{
    public function normalize($object, ?string $format = null, array $context = []): array
    {
        return [];
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return is_object($data);
    }

    public function getSupportedTypes(?string $format): array
    {
        return ['object' => true];
    }
}
