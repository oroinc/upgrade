<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Semadiff\Analysis;

final class BcDetailFilter
{
    private const NON_BC_PREFIXES = [
        'Method return type added',
        'Method param type added',
        'Method param added (optional)',
        'Method visibility loosened',
        'Property type added',
        'Property visibility loosened',
        'Method body changed',
        'Property default value changed',
        'Constant value changed',
        'Method added',
        'Property added',
        'Constant added',
        'Class added',
    ];

    /**
     * Filter detail strings to only BC-breaking items.
     *
     * @param string[] $details
     * @return string[]
     */
    public function filterBcDetails(array $details): array
    {
        $filtered = array_values(array_filter($details, function (string $detail): bool {
            foreach (self::NON_BC_PREFIXES as $prefix) {
                if (str_starts_with($detail, $prefix . ': ') || $detail === $prefix) {
                    return false;
                }
            }

            return true;
        }));

        // Remove "Constructor changed" shorthand when no other BC details reference __construct
        $hasConstructorBc = false;
        foreach ($filtered as $detail) {
            if (!str_starts_with($detail, 'Constructor changed: ') && str_contains($detail, '::__construct')) {
                $hasConstructorBc = true;
                break;
            }
        }

        if (!$hasConstructorBc) {
            $filtered = array_values(array_filter(
                $filtered,
                static fn (string $detail) => !str_starts_with($detail, 'Constructor changed: '),
            ));
        }

        return $filtered;
    }

    /**
     * Filter detail strings to only removed-member items.
     *
     * @param string[] $details
     * @return string[]
     */
    public function filterRemovedDetails(array $details): array
    {
        return array_values(array_filter($details, static function (string $detail): bool {
            return str_starts_with($detail, 'Method removed: ')
                || str_starts_with($detail, 'Property removed: ')
                || str_starts_with($detail, 'Constant removed: ');
        }));
    }

    /**
     * Extract changed method names from BC detail strings.
     *
     * @param string[] $bcDetails
     * @return array{methods: string[], constructorChanged: bool}
     */
    public function extractChangedMethods(array $bcDetails): array
    {
        $methods = [];
        $constructorChanged = false;

        $methodPrefixes = [
            'Method return type changed',
            'Method visibility tightened',
            'Method made abstract',
            'Method made final',
            'Method static changed',
            'Method param added (required)',
            'Method param removed',
            'Method param type changed',
            'Method param renamed',
            'Method param modifier changed',
            'Method removed',
        ];

        foreach ($bcDetails as $detail) {
            if (str_starts_with($detail, 'Constructor changed: ')) {
                $constructorChanged = true;
                continue;
            }

            foreach ($methodPrefixes as $prefix) {
                if (str_starts_with($detail, $prefix . ': ')) {
                    $member = substr($detail, strlen($prefix) + 2);
                    $colonPos = strpos($member, '::');
                    if ($colonPos !== false) {
                        $methodName = substr($member, $colonPos + 2);
                        $methods[] = $methodName;
                    }
                    break;
                }
            }
        }

        if ($constructorChanged && !in_array('__construct', $methods, true)) {
            $methods[] = '__construct';
        }

        return [
            'methods' => array_values(array_unique($methods)),
            'constructorChanged' => $constructorChanged,
        ];
    }
}
