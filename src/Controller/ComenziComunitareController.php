<?php

namespace App\Controller;

use App\Entity\ComenziComunitare;
use App\Form\ComenziComunitareType;
use App\Repository\ComenziComunitareRepository;
use App\Repository\ParcAutoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/comenzi/comunitare')]
class ComenziComunitareController extends AbstractController
{
    #[Route('/', name: 'app_comenzi_comunitare_index', methods: ['GET'])]
    public function index(ComenziComunitareRepository $comenziComunitareRepository): Response
    {
        return $this->render('comenzi_comunitare/index.html.twig', [
            'comenzi_comunitares' => $comenziComunitareRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_comenzi_comunitare_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ParcAutoRepository $parcAutoRepository): Response
    {
        $comenziComunitare = new ComenziComunitare();
        $form = $this->createForm(ComenziComunitareType::class, $comenziComunitare);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($comenziComunitare);
            $entityManager->flush();
            return $this->redirectToRoute('app_comenzi_comunitare_index', [], Response::HTTP_SEE_OTHER);
        }

        $parcAutoList = $parcAutoRepository->findAll();

        return $this->render('comenzi_comunitare/new.html.twig', [
            'form' => $form->createView(),
            'parc_auto_list' => $parcAutoList,
        ]);
    }

    #[Route('/{id}', name: 'app_comenzi_comunitare_show', methods: ['GET'])]
    public function show(ComenziComunitare $comenziComunitare): Response
    {
        return $this->render('comenzi_comunitare/show.html.twig', [
            'comenzi_comunitare' => $comenziComunitare,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_comenzi_comunitare_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ComenziComunitare $comenziComunitare, EntityManagerInterface $entityManager, ParcAutoRepository $parcAutoRepository): Response
    {
        $form = $this->createForm(ComenziComunitareType::class, $comenziComunitare);
        $form->get('nr_auto')->setData($comenziComunitare->getNrAuto() ? $comenziComunitare->getNrAuto()->getNrAuto() : '');
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_comenzi_comunitare_index', [], Response::HTTP_SEE_OTHER);
        }
    
        $parcAutoList = $parcAutoRepository->findAll();
    
        return $this->render('comenzi_comunitare/edit.html.twig', [
            'form' => $form->createView(),
            'parc_auto_list' => $parcAutoList,
        ]);
    }

    #[Route('/{id}', name: 'app_comenzi_comunitare_delete', methods: ['POST'])]
    public function delete(Request $request, ComenziComunitare $comenziComunitare, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$comenziComunitare->getId(), $request->request->get('_token'))) {
            $entityManager->remove($comenziComunitare);
            $entityManager->flush();
        }
        return $this->redirectToRoute('app_comenzi_comunitare_index', [], Response::HTTP_SEE_OTHER);
    }
}