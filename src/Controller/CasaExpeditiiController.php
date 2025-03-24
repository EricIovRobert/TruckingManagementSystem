<?php

namespace App\Controller;

use App\Entity\CasaExpeditii;
use App\Form\CasaExpeditiiType;
use App\Repository\CasaExpeditiiRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/casa-expeditii')]
class CasaExpeditiiController extends AbstractController
{
    #[Route('/', name: 'app_casa_expeditii_index', methods: ['GET'])]
    public function index(CasaExpeditiiRepository $casaExpeditiiRepository): Response
    {
        $expeditii = $casaExpeditiiRepository->findAll();

        return $this->render('casa_expeditii/index.html.twig', [
            'expeditii' => $expeditii,
        ]);
    }

    #[Route('/new', name: 'app_casa_expeditii_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $casaExpeditii = new CasaExpeditii();
        $form = $this->createForm(CasaExpeditiiType::class, $casaExpeditii);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($casaExpeditii);
            $entityManager->flush();

            return $this->redirectToRoute('app_casa_expeditii_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('casa_expeditii/new.html.twig', [
            'casa_expeditii' => $casaExpeditii,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_casa_expeditii_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CasaExpeditii $casaExpeditii, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CasaExpeditiiType::class, $casaExpeditii);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_casa_expeditii_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('casa_expeditii/edit.html.twig', [
            'casa_expeditii' => $casaExpeditii,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_casa_expeditii_delete', methods: ['POST'])]
    public function delete(Request $request, CasaExpeditii $casaExpeditii, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$casaExpeditii->getId(), $request->request->get('_token'))) {
            $entityManager->remove($casaExpeditii);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_casa_expeditii_index', [], Response::HTTP_SEE_OTHER);
    }
}