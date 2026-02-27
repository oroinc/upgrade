<?php

declare(strict_types=1);

namespace DT\Bundle\EntityBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

#[ORM\Entity(repositoryClass: OrderErrorCaseRepository::class)]
#[ORM\Table(name: 'dt_order_error_case')]
#[ORM\HasLifecycleCallbacks]
#[Config(defaultValues: ['dataaudit' => ['auditable' => true]])]
class OrderErrorCase implements DatesAwareInterface, ExtendEntityInterface
{
    use ExtendEntityTrait;

    public const string ORDER_ERROR_STATUS = 'orderErrorStatus';
    public const string FREIGHT_CLAIM_STATUS = 'freightClaimStatus';

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected $id;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'salesforce_id', length: 18, type: 'string', nullable: true, unique: true)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true], 'importexport' => ['identity' => -1]])]
    protected $salesforceId;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'subject', length: 255, type: 'string', nullable: true)]
    protected $subject;

    /**
     * @var Order|null
     */
    #[ORM\ManyToOne(targetEntity: Order::class)]
    #[ORM\JoinColumn(name: 'order_id', referencedColumnName: 'id', onDelete: 'SET NULL', nullable: true)]
    protected $order;

    /**
     * @var Collection|OrderErrorCaseLineItem[]
     */
    #[ORM\OneToMany(
        targetEntity: OrderErrorCaseLineItem::class,
        mappedBy: 'orderErrorCase',
        cascade: ['ALL'],
        orphanRemoval: true,
    )]
    #[ORM\OrderBy(['id' => 'ASC'])]
    protected $lineItems;

    public function __construct()
    {
        $this->lineItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSalesforceId(): ?string
    {
        return $this->salesforceId;
    }

    public function setSalesforceId(?string $salesforceId): self
    {
        $this->salesforceId = $salesforceId;
        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function addLineItem(OrderErrorCaseLineItem $lineItem): OrderErrorCase
    {
        if (!$this->lineItems->contains($lineItem)) {
            $this->lineItems[] = $lineItem;
            $lineItem->setOrderErrorCase($this);
        }

        return $this;
    }

    #[ORM\PrePersist]
    public function prePersist()
    {
        if (!$this->createdAt) {
            $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        }

        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
