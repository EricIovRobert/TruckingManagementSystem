<?php

namespace App\Form;

use App\Entity\Retururi;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
            ->add('kg', null, [
                'label' => 'Tone', // Schimbăm label-ul în "Tone"
            ])
            ->add('pret')
            ->add('liber')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Retururi::class,
        ]);
    }
}