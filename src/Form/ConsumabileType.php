<?php

namespace App\Form;

use App\Entity\Consumabile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConsumabileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nume', TextType::class, ['label' => 'Nume Consumabil'])
            ->add('pret_maxim', NumberType::class, ['label' => 'Preț Maxim'])
            ->add('km_utilizare_max', NumberType::class, ['label' => 'KM Utilizare Max', 'required' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Consumabile::class,
        ]);
    }
}