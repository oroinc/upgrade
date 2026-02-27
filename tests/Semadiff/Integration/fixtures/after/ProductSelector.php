<?php

declare(strict_types=1);

namespace DT\Bundle\EntityBundle\Entity;

use DT\Bundle\EntityBundle\Entity\Repository\ProductSelectorRepository;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;

#[ORM\Entity(repositoryClass: ProductSelectorRepository::class)]
#[ORM\Table(name: 'dt_product_selector')]
#[Config(defaultValues: ['dataaudit' => ['auditable' => true]])]
class ProductSelector implements DatesAwareInterface, ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: true)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.created_at']])]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: true)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.updated_at']])]
    protected ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: OrganizationInterface::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?OrganizationInterface $organization = null;

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getOrganization(): ?OrganizationInterface
    {
        return $this->organization;
    }

    public function setOrganization(?OrganizationInterface $organization): self
    {
        $this->organization = $organization;
        return $this;
    }
}
