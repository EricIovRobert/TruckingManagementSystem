<?php

namespace App\Form;

use App\Entity\DatoriiSofer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DatoriiSoferType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nume_sofer', TextType::class, [
                'label' => 'Nume Șofer',
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('denumire', TextType::class, [
                'label' => 'Denumire Datorie',
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('suma', NumberType::class, [
                'label' => 'Suma',
                'required' => true,
                'attr' => ['class' => 'form-control', 'step' => '0.01'],
            ])
            ->add('data', DateType::class, [
                'label' => 'Data',
                'widget' => 'single_text',
                'required' => true,
                'attr' => ['class' => 'form-control datepicker'],
                'format' => 'dd/MM/yyyy',
                'html5' => false,
            ])
            ->add('achitata', NumberType::class, [
                'label' => 'Achitată',
                'required' => false,
                'attr' => ['class' => 'form-control', 'step' => '0.01'],
            ])
            ->add('observatii', TextType::class, [
                'label' => 'Observații',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DatoriiSofer::class,
        ]);
    }
}