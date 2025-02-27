<?php

namespace App\Form;

use App\Entity\Comenzi;
use App\Entity\ParcAuto;
use App\Repository\ParcAutoRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ComenziType extends AbstractType
{
    private $parcAutoRepository;

    public function __construct(ParcAutoRepository $parcAutoRepository)
    {
        $this->parcAutoRepository = $parcAutoRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('parcAutoNr', TextType::class, [ // Câmp text pentru nrAuto
                'label' => 'Mașină (Nr. Înmatriculare)',
                'mapped' => false, // Nu mapăm direct pe entitate
                'attr' => [
                    'list' => 'parc_auto_list', // Legăm la datalist
                    'autocomplete' => 'off', // Dezactivăm autocomplete-ul browserului
                ],
            ])
            ->add('sofer')
            ->add('dataStart')
            ->add('dataStop')
            ->add('numarKm')
            ->add('profit')
        ;

        // Adăugăm logica pentru a seta parcAuto pe baza nrAuto
        $builder->addEventListener(
            \Symfony\Component\Form\FormEvents::POST_SUBMIT,
            function (\Symfony\Component\Form\FormEvent $event) {
                $form = $event->getForm();
                $comanda = $event->getData();
                $nrAuto = $form->get('parcAutoNr')->getData();

                if ($nrAuto) {
                    $parcAuto = $this->parcAutoRepository->findOneBy(['nrAuto' => $nrAuto]);
                    if ($parcAuto) {
                        $comanda->setParcAuto($parcAuto);
                    }
                }
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Comenzi::class,
        ]);
    }
}