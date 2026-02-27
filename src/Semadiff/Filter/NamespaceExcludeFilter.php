<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Semadiff\Filter;

final class NamespaceExcludeFilter
{
    /** @var string[] compiled regex patterns */
    private array $regexes;

    /**
     * @param string[] $patterns Glob-like patterns, e.g. ['DT\Bundle\TestBundle\*', '*\Tests\*']
     */
    public function __construct(array $patterns)
    {
        $this->regexes = [];
        foreach ($patterns as $pattern) {
            $pattern = trim($pattern);
            if ($pattern === '') {
                continue;
            }
            $this->regexes[] = self::patternToRegex($pattern);
        }
    }

    /**
     * Parse a comma-separated exclude string into a filter instance.
     */
    public static function fromString(?string $raw): self
    {
        if ($raw === null || trim($raw) === '') {
            return new self([]);
        }

        return new self(array_map('trim', explode(',', $raw)));
    }

    public function hasPatterns(): bool
    {
        return $this->regexes !== [];
    }

    public function isExcluded(string $fqcn): bool
    {
        foreach ($this->regexes as $regex) {
            if (preg_match($regex, $fqcn) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string[] $fqcns
     * @return string[]
     */
    public function filterList(array $fqcns): array
    {
        return array_values(array_filter($fqcns, fn (string $fqcn) => !$this->isExcluded($fqcn)));
    }

    /**
     * Filter a grouped map: remove excluded target keys and excluded dependents from values.
     * Entries with no remaining dependents are removed entirely.
     *
     * @param array<string, string[]> $grouped target FQCN → dependent FQCNs
     * @return array<string, string[]>
     */
    public function filterGrouped(array $grouped): array
    {
        $result = [];
        foreach ($grouped as $target => $dependents) {
            if ($this->isExcluded($target)) {
                continue;
            }
            $filtered = array_values(array_filter($dependents, fn (string $fqcn) => !$this->isExcluded($fqcn)));
            if ($filtered !== []) {
                $result[$target] = $filtered;
            }
        }

        return $result;
    }

    /**
     * Convert a glob-like pattern to a regex.
     * `*` matches any sequence of characters (including namespace separators).
     * Everything else is matched literally.
     */
    private static function patternToRegex(string $pattern): string
    {
        $regex = '/^';
        for ($i = 0, $len = strlen($pattern); $i < $len; $i++) {
            $ch = $pattern[$i];
            if ($ch === '*') {
                $regex .= '.*';
            } else {
                $regex .= preg_quote($ch, '/');
            }
        }

        return $regex . '$/';
    }
}
