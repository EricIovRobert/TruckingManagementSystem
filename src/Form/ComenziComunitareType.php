<?php

namespace App\Form;

use App\Entity\ComenziComunitare;
use App\Entity\ParcAuto;
use App\Repository\ParcAutoRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

class ComenziComunitareType extends AbstractType
{
    private $parcAutoRepository;

    public function __construct(ParcAutoRepository $parcAutoRepository)
    {
        $this->parcAutoRepository = $parcAutoRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nr_auto', TextType::class, [
                'label' => 'Mașină (Nr. Înmatriculare)',
                'mapped' => false,
                'attr' => [
                    'list' => 'parc_auto_list',
                    'autocomplete' => 'off',
                ],
            ])
            ->add('remorca', TextType::class, [
                'label' => 'Remorcă (Nr. Înmatriculare)',
                'required' => false,
                'attr' => [
                    'list' => 'remorca_list',
                    'autocomplete' => 'off',
                ],
            ])
            ->add('sofer', TextType::class, ['label' => 'Șofer'])
            ->add('data_start', DateType::class, [
                'label' => 'Data Start',
                'widget' => 'single_text',
                'attr' => ['class' => 'datepicker'],
                'format' => 'dd/MM/yyyy',
                'html5' => false,
            ])
            ->add('data_stop', DateType::class, [
                'label' => 'Data Stop',
                'widget' => 'single_text',
                'attr' => ['class' => 'datepicker'],
                'format' => 'dd/MM/yyyy',
                'html5' => false,
                'required' => false,
            ])
            ->add('nr_km', NumberType::class, [
                'label' => 'Număr KM',
                'required' => false,
            ])
            ->add('kg', NumberType::class, ['label' => 'Kilograme'])
            ->add('pret', NumberType::class, ['label' => 'Preț'])
            ->add('firma', TextType::class, ['label' => 'Firmă'])
        ;

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();
                $comanda = $event->getData();
                $nrAuto = $form->get('nr_auto')->getData();

                if ($nrAuto) {
                    $parcAuto = $this->parcAutoRepository->findOneBy(['nrAuto' => $nrAuto]);
                    if ($parcAuto) {
                        $comanda->setNrAuto($parcAuto);
                        $comanda->setNrAutoSnapshot($nrAuto);
                    }
                }
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ComenziComunitare::class,
        ]);
    }
}