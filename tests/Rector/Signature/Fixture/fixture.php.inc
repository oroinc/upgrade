<?php

namespace Rector\Signature\Fixture;

class Foo extends Bar
{
    protected $property;

    protected function method(string $argument): string
    {
        return $argument;
    }
}

class Bar
{
    protected int $property;

    protected function method(int $argument): int
    {
        return $argument;
    }
}
?>
-----
<?php

namespace Rector\Signature\Fixture;

class Foo extends Bar
{
    protected int $property;

    protected function method(int $argument): int
    {
        return $argument;
    }
}

class Bar
{
    protected int $property;

    protected function method(int $argument): int
    {
        return $argument;
    }
}
?>
