<?php

namespace App\Entity;

class RelationshipEntity
{
    /**
     * @Doctrine\ORM\Mapping\OneToMany(targetEntity="Comment", mappedBy="post")
     */
    private $comments;

    /**
     * @Doctrine\ORM\Mapping\ManyToOne(targetEntity="User")
     * @Doctrine\ORM\Mapping\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $author;
}

-----
<?php

namespace App\Entity;

class RelationshipEntity
{
    /**
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="post")
     */
    private $comments;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $author;
}
