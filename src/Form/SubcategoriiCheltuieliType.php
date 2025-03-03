<?php

namespace App\Form;

use App\Entity\SubcategoriiCheltuieli;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubcategoriiCheltuieliType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nume', TextType::class, ['label' => 'Nume Subcategorie'])
            ->add('pret_standard', NumberType::class, ['label' => 'Preț Standard', 'required' => false])
            ->add('pret_per_l', NumberType::class, ['label' => 'Preț per Litru', 'required' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SubcategoriiCheltuieli::class,
        ]);
    }
}