<?php

namespace App\Form;

use App\Entity\Cheltuieli;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ComenziCheltuieliBatchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cheltuielis', CollectionType::class, [
                'entry_type' => CheltuieliType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null, // We'll handle the array of objects manually in the controller or use a wrapper if needed, but array is fine usually for this specific simple case or better yet, we binding to the Comanda entity? No, existing `newCheltuiala` creates NEW objects. Mapping to `Comanda` implies editing existing collection. 
            // Actually, best practice for "Adding Multiple NEW items" is often just a form with a collection, not necessarily bound to the Parent Entity if we don't want to load all existing ones.
            // Let's bind it to a simple array or DTO.
        ]);
    }
}
