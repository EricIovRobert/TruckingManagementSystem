<?php

namespace App\Controller;

use App\Entity\Consumabile;
use App\Entity\CategoriiCheltuieli; // Adăugat importul corect
use App\Form\ConsumabileType;
use App\Repository\ConsumabileRepository;
use App\Repository\CategoriiCheltuieliRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/consumabile')]
class ConsumabileController extends AbstractController
{
    #[Route('/', name: 'app_consumabile_index', methods: ['GET'])]
    public function index(ConsumabileRepository $consumabileRepository): Response
    {
        return $this->render('consumabile/index.html.twig', [
            'consumabile' => $consumabileRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_consumabile_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, CategoriiCheltuieliRepository $categoriiCheltuieliRepository): Response
    {
        $consumabil = new Consumabile();
        
        // Verificăm dacă categoria "Consumabile" există; dacă nu, o creăm
        $categoriaConsumabile = $categoriiCheltuieliRepository->findOneBy(['nume' => 'Consumabile']);
        if (!$categoriaConsumabile) {
            $categoriaConsumabile = new CategoriiCheltuieli();
            $categoriaConsumabile->setNume('Consumabile');
            $entityManager->persist($categoriaConsumabile);
            // Nu apelăm flush() încă, o vom salva împreună cu consumabilul
        }
        
        $consumabil->setCategorie($categoriaConsumabile);

        $form = $this->createForm(ConsumabileType::class, $consumabil);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($consumabil);
            $entityManager->flush(); // Salvează atât consumabilul, cât și categoria, dacă a fost creată
            return $this->redirectToRoute('app_consumabile_index');
        }

        return $this->render('consumabile/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_consumabile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Consumabile $consumabil, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ConsumabileType::class, $consumabil);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_consumabile_index');
        }

        return $this->render('consumabile/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_consumabile_delete', methods: ['POST'])]
    public function delete(Request $request, Consumabile $consumabil, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$consumabil->getId(), $request->request->get('_token'))) {
            $entityManager->remove($consumabil);
            $entityManager->flush();
        }
        return $this->redirectToRoute('app_consumabile_index');
    }
}