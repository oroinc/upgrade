<?php
namespace App\Tests;

use App\Mocks\TestClassExplicit;

class Test
{
    public function test()
    {
        $obj = new TestClassExplicit();
        $obj->name = 'test_name';
        $obj->value = 42;
        $obj->description = 'This should not be transformed';

        return $obj;
    }
}

-----
<?php
namespace App\Tests;

use App\Mocks\TestClassExplicit;

class Test
{
    public function test()
    {
        $obj = new TestClassExplicit(name: 'test_name', value: 42);
        $obj->description = 'This should not be transformed';

        return $obj;
    }
}
