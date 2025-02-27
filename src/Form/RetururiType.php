<?php

namespace App\Form;

use App\Entity\Retururi;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RetururiType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firma')
            ->add('rutaIncarcare')
            ->add('rutaDescarcare')
            ->add('kg')
            ->add('pret')
            ->add('liber')
            ->add('facturat')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Retururi::class,
        ]);
    }
}