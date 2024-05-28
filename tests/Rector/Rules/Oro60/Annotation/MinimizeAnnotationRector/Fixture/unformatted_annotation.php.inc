<?php

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\Oro60\Annotation\MinimizeAnnotationRector\Fixture;

/**
 * Represents some test class.
 *
 * @Config(
 *                          defaultValues={
 *              "slug"={"source"="titles" }
 *     })
 */
class SomeTestClass
{
    public function run()
    {
        return 'STRING';
    }
}

-----
<?php

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\Oro60\Annotation\MinimizeAnnotationRector\Fixture;

/**
 * Represents some test class.
 *
 * @Config(defaultValues={"slug"={"source"="titles"}})
 */
class SomeTestClass
{
    public function run()
    {
        return 'STRING';
    }
}
