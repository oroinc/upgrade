<?php

namespace Oro\Tests\Rector\Rules\Oro60\Annotation\MinimizeAnnotationRector\Fixture;

/**
 * Represents some test class.
 *
 * @Config(
 *      defaultValues={
 *          "dataaudit"={
 *              "auditable"=true
 *
 *          },
 *          "slug"={
 *              "source"="titles"
 *          }
 *     }
 * )
 */
class SomeTestClass
{
    /**
     * @ConfigField(
     *
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    public function run()
    {
        return 'STRING';
    }
}

-----
<?php

namespace Oro\Tests\Rector\Rules\Oro60\Annotation\MinimizeAnnotationRector\Fixture;

/**
 * Represents some test class.
 *
 * @Config(defaultValues={"dataaudit"={"auditable"=true},"slug"={"source"="titles"}})
 */
class SomeTestClass
{
    /**
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    public function run()
    {
        return 'STRING';
    }
}
