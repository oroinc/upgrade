<?php

declare(strict_types=1);

namespace DT\Bundle\AccountPlanBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GoOpportunityOwnerSelectType extends AbstractType
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('owner', SalesRepUserSelectType::class, [
            'label' => 'dt.accountplan.opportunity.owner.label',
        ]);
    }

    protected function getRsm(): ?string
    {
        $regionId = $this->requestStack->getMainRequest()->get('regionId');
        return $regionId;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null]);
    }

    public function getParent()
    {
        return EntityType::class;
    }

    public function getBlockPrefix()
    {
        return 'dt_go_opportunity_owner_select';
    }
}
