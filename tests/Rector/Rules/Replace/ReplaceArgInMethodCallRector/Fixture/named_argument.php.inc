<?php

namespace Test;

use App\Mocks\Foo;

final class SomeClass
{
    public function run(Foo $foo): void
    {
        $foo->bar(name: 'n', mode: 'old');
    }
}
-----
<?php

namespace Test;

use App\Mocks\Foo;

final class SomeClass
{
    public function run(Foo $foo): void
    {
        $foo->bar(name: 'n', mode: 'new');
    }
}
