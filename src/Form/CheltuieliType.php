<?php

namespace App\Form;

use App\Entity\CategoriiCheltuieli;
use App\Entity\SubcategoriiCheltuieli;
use App\Entity\Cheltuieli;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheltuieliType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('categorie', EntityType::class, [
                'class' => CategoriiCheltuieli::class,
                'choice_label' => 'nume',
                'placeholder' => 'Selectează o categorie',
                'required' => true,
            ])
            ->add('subcategorie', TextType::class, [
                'label' => 'Subcategorie',
                'required' => false,
                'mapped' => false, // Gestionăm manual maparea
                'attr' => [
                    'style' => 'display: none;', // Ascundem câmpul text, vom folosi un select în JavaScript
                    'data-pret-standard' => '',
                ],
            ])
            ->add('suma', NumberType::class, [
                'label' => 'Suma',
                'required' => true,
            ])
            ->add('litri_motorina', NumberType::class, [
                'label' => 'Litri Motorină (opțional)',
                'required' => false,
            ])
            ->add('tva', NumberType::class, [
                'label' => 'TVA (opțional)',
                'required' => false,
            ])
            ->add('comision_tva', NumberType::class, [
                'label' => 'Comision TVA (opțional)',
                'required' => false,
            ])
            ->add('data_cheltuiala', DateType::class, [
                'label' => 'Data cheltuielii',
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('descriere', TextType::class, [
                'label' => 'Descriere',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cheltuieli::class,
            'allow_extra_fields' => true, // Permitem câmpuri suplimentare
        ]);
    }
}