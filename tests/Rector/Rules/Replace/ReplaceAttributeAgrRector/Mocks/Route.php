<?php

declare(strict_types=1);

namespace App\Mocks;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class Route
{
    /**
     * @param string $name
     * @param array<string> $methods
     */
    public function __construct(
        string $name = '',
        array $methods = [],
    ) {
    }
}
