<?php

namespace App\Entity;

/**
 * Test case with no matching annotations - should remain unchanged
 * @SomeOther\Annotation\Entity
 * @AnotherNamespace\Column(type="string")
 */
class NoMatchingAnnotationsEntity
{
    /**
     * @SomeOther\Column(type="integer")
     */
    private $id;

    /**
     * @UnknownAnnotation
     */
    private $field;
}

-----
<?php

namespace App\Entity;

/**
 * Test case with no matching annotations - should remain unchanged
 * @SomeOther\Annotation\Entity
 * @AnotherNamespace\Column(type="string")
 */
class NoMatchingAnnotationsEntity
{
    /**
     * @SomeOther\Column(type="integer")
     */
    private $id;

    /**
     * @UnknownAnnotation
     */
    private $field;
}
