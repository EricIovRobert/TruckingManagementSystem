<?php

namespace App\Form;

use App\Entity\CasaExpeditii;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CasaExpeditiiType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('numeClient', TextType::class, [
                'label' => 'Nume Client',
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('nrComandaClient', TextType::class, [
                'label' => 'Nr. Comandă Client',
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('pretClient', NumberType::class, [
                'label' => 'Preț Client',
                'required' => true,
                'attr' => ['class' => 'form-control', 'step' => '0.01'],
            ])
            ->add('numeTransportator', TextType::class, [
                'label' => 'Nume Transportator',
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('pretTransportator', NumberType::class, [
                'label' => 'Preț Transportator',
                'required' => true,
                'attr' => ['class' => 'form-control', 'step' => '0.01'],
            ])
            ->add('nrComandaTransportator', TextType::class, [
                'label' => 'Nr. Comandă Transportator',
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('scadenta', DateType::class, [
                'label' => 'Scadența',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control datepicker'],
                'format' => 'dd/MM/yyyy',
                'html5' => false,
            ])
            ->add('data_platii', DateType::class, [
                'label' => 'Data Plății',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control datepicker'],
                'format' => 'dd/MM/yyyy',
                'html5' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CasaExpeditii::class,
        ]);
    }
}