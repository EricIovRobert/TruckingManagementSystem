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
            ->add('parcAutoNr', TextType::class, [
                'label' => 'Mașină (Nr. Înmatriculare)',
                'mapped' => false,
                'attr' => [
                    'list' => 'parc_auto_list',
                    'autocomplete' => 'off',
                ],
            ])
            ->add('nrAccidentAuto', TextType::class, [
                'label' => 'Mașină Accident (Nr. Înmatriculare)',
                'required' => false,
                'attr' => [
                    'list' => 'parc_auto_list',
                    'autocomplete' => 'off',
                ],
            ])
            ->add('sofer')
            ->add('dataStart')
            ->add('dataStop', null, [
                'required' => false,
            ])
            ->add('numarKm', null, [
                'required' => false,
            ])
            ->add('profit')
        ;

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
                        $comanda->setParcAutoNrSnapshot($nrAuto);
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