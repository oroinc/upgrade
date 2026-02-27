<?php

declare(strict_types=1);

namespace DT\Bundle\EntityBundle\Entity;

use DT\Bundle\EntityBundle\Model\ExtendProductSelector;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * @ORM\Entity(repositoryClass="DT\Bundle\EntityBundle\Entity\Repository\ProductSelectorRepository")
 * @ORM\Table(name="dt_product_selector")
 * @Config(defaultValues={"dataaudit"={"auditable"=true}})
 */
class ProductSelector extends ExtendProductSelector implements DatesAwareInterface
{
    /**
     * @var \DateTime|null
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     * @ConfigField(defaultValues={"entity"={"label"="oro.ui.created_at"}})
     */
    protected $createdAt;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     * @ConfigField(defaultValues={"entity"={"label"="oro.ui.updated_at"}})
     */
    protected $updatedAt;

    /**
     * @var OrganizationInterface|null
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $organization;

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
