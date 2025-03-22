<?php

namespace App\Controller;

use App\Entity\DatoriiSofer;
use App\Form\DatoriiSoferType;
use App\Repository\DatoriiSoferRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/datorii/sofer')]
class DatoriiSoferController extends AbstractController
{
    #[Route('/', name: 'app_datorii_sofer_index', methods: ['GET'])]
    public function index(DatoriiSoferRepository $datoriiSoferRepository): Response
    {
        $datorii = $datoriiSoferRepository->findAll();

        return $this->render('datorii_sofer/index.html.twig', [
            'datorii' => $datorii,
        ]);
    }

    #[Route('/new', name: 'app_datorii_sofer_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $datorie = new DatoriiSofer();
        $form = $this->createForm(DatoriiSoferType::class, $datorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($datorie);
            $entityManager->flush();

            return $this->redirectToRoute('app_datorii_sofer_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('datorii_sofer/new.html.twig', [
            'datorie' => $datorie,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_datorii_sofer_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, DatoriiSofer $datorie, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DatoriiSoferType::class, $datorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_datorii_sofer_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('datorii_sofer/edit.html.twig', [
            'datorie' => $datorie,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_datorii_sofer_delete', methods: ['POST'])]
    public function delete(Request $request, DatoriiSofer $datorie, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$datorie->getId(), $request->request->get('_token'))) {
            $entityManager->remove($datorie);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_datorii_sofer_index', [], Response::HTTP_SEE_OTHER);
    }

    
}