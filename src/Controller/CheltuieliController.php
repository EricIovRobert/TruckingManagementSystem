<?php

namespace App\Controller;

use App\Entity\Cheltuieli;
use App\Entity\Consumabile;
use App\Entity\SubcategoriiCheltuieli;
use App\Form\CheltuieliType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/rapoarte')]
class CheltuieliController extends AbstractController
{
    #[Route('/cheltuieli', name: 'app_cheltuieli_list', methods: ['GET'])]
    public function listCheltuieli(EntityManagerInterface $em): Response
    {
        $cheltuieli = $em->getRepository(Cheltuieli::class)->findAll();

        return $this->render('cheltuieli/index.html.twig', [
            'cheltuieli' => $cheltuieli,
        ]);
    }

    #[Route('/cheltuieli/new', name: 'app_cheltuieli_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $cheltuiala = new Cheltuieli();
        $cheltuiala->setDataCheltuiala(new \DateTime());

        $form = $this->createForm(CheltuieliType::class, $cheltuiala);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $categorie = $cheltuiala->getCategorie();
            $subcategorieId = $form->get('subcategorie')->getData();

            if ($categorie && $categorie->getNume() === 'Consumabile') {
                if ($subcategorieId) {
                    $consumabil = $entityManager->getRepository(Consumabile::class)->find($subcategorieId);
                    if ($consumabil) {
                        $cheltuiala->setConsumabil($consumabil);
                        $cheltuiala->setSubcategorie(null);
                        // Pentru cheltuieli de sine stătătoare, suma este introdusă manual
                    }
                }
            } else {
                if ($subcategorieId) {
                    $subcategorie = $entityManager->getRepository(SubcategoriiCheltuieli::class)->find($subcategorieId);
                    if ($subcategorie) {
                        $cheltuiala->setSubcategorie($subcategorie);
                        $cheltuiala->setConsumabil(null);
                    }
                }
            }

            $entityManager->persist($cheltuiala);
            $entityManager->flush();

            return $this->redirectToRoute('app_cheltuieli_list');
        }

        return $this->render('cheltuieli/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/cheltuieli/{id}/edit', name: 'app_cheltuieli_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Cheltuieli $cheltuiala, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CheltuieliType::class, $cheltuiala);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $categorie = $cheltuiala->getCategorie();
            $subcategorieId = $form->get('subcategorie')->getData();

            if ($categorie && $categorie->getNume() === 'Consumabile') {
                if ($subcategorieId) {
                    $consumabil = $entityManager->getRepository(Consumabile::class)->find($subcategorieId);
                    if ($consumabil) {
                        $cheltuiala->setConsumabil($consumabil);
                        $cheltuiala->setSubcategorie(null);
                        // Suma este introdusă manual, nu recalculăm aici
                    }
                }
            } else {
                if ($subcategorieId) {
                    $subcategorie = $entityManager->getRepository(SubcategoriiCheltuieli::class)->find($subcategorieId);
                    if ($subcategorie) {
                        $cheltuiala->setSubcategorie($subcategorie);
                        $cheltuiala->setConsumabil(null);
                    }
                }
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_cheltuieli_list');
        }

        return $this->render('cheltuieli/edit.html.twig', [
            'form' => $form->createView(),
            'cheltuiala' => $cheltuiala,
        ]);
    }

    #[Route('/cheltuieli/{id}/delete', name: 'app_cheltuieli_delete', methods: ['POST'])]
    public function delete(Request $request, Cheltuieli $cheltuiala, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$cheltuiala->getId(), $request->request->get('_token'))) {
            $entityManager->remove($cheltuiala);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_cheltuieli_list');
    }
}