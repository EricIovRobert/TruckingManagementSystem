<?php

namespace App\Controller;

use App\Entity\Tururi;
use App\Form\TururiType;
use App\Repository\ComenziRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tururi')]
class TururiController extends AbstractController
{
    #[Route('/new/{comanda_id}', name: 'app_tururi_new', methods: ['GET', 'POST'])]
    public function new(Request $request, int $comanda_id, ComenziRepository $comenziRepository, EntityManagerInterface $entityManager): Response
    {
        $comanda = $comenziRepository->find($comanda_id);
        if (!$comanda) {
            throw $this->createNotFoundException('Comanda nu a fost găsită');
        }

        $tur = new Tururi();
        $tur->setComanda($comanda);
        $form = $this->createForm(TururiType::class, $tur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tur);
            $entityManager->flush();
            return $this->redirectToRoute('app_comenzi_show', ['id' => $comanda->getId()]);
        }

        return $this->render('tururi/new.html.twig', [
            'form' => $form->createView(),
            'comanda' => $comanda,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_tururi_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Tururi $tur, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TururiType::class, $tur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_comenzi_show', ['id' => $tur->getComanda()->getId()]);
        }

        return $this->render('tururi/edit.html.twig', [
            'form' => $form->createView(),
            'tur' => $tur,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_tururi_delete', methods: ['POST'])]
    public function delete(Request $request, Tururi $tur, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $tur->getId(), $request->request->get('_token'))) {
            $comandaId = $tur->getComanda()->getId();
            $entityManager->remove($tur);
            $entityManager->flush();
            return $this->redirectToRoute('app_comenzi_show', ['id' => $comandaId]);
        }

        return $this->redirectToRoute('app_comenzi_show', ['id' => $tur->getComanda()->getId()]);
    }

    #[Route('/{id}/update-facturat', name: 'app_tururi_update_facturat', methods: ['POST'])]
    public function updateFacturat(Request $request, Tururi $tur, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            if (!$tur) {
                throw $this->createNotFoundException('Turul nu a fost găsit');
            }

            $facturat = filter_var($request->request->get('facturat', false), FILTER_VALIDATE_BOOLEAN);
            $tur->setFacturat($facturat);
            $entityManager->flush();

            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}