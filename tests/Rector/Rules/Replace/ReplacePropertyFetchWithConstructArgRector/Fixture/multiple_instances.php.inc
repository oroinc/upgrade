<?php

namespace App\Tests;

use App\Mocks\TestClassAutoDetect;

class Test
{
    public function test()
    {
        $obj1 = new TestClassAutoDetect();
        $obj1->first = 'first_value';
        $obj1->second = 'second_value';

        $obj2 = new TestClassAutoDetect();
        $obj2->first = 'another_first';
        $obj2->second = 'another_second';
        $obj2->third = 'another_third';

        return [$obj1, $obj2];
    }
}

-----
<?php

namespace App\Tests;

use App\Mocks\TestClassAutoDetect;

class Test
{
    public function test()
    {
        $obj1 = new TestClassAutoDetect(first: 'first_value', second: 'second_value');

        $obj2 = new TestClassAutoDetect(first: 'another_first', second: 'another_second', third: 'another_third');

        return [$obj1, $obj2];
    }
}
