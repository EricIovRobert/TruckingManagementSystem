<?php

namespace App\Controller;

use App\Entity\Retururi;
use App\Form\RetururiType;
use App\Repository\ComenziRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/retururi')]
class RetururiController extends AbstractController
{
    #[Route('/new/{comanda_id}', name: 'app_retururi_new', methods: ['GET', 'POST'])]
    public function new(Request $request, int $comanda_id, ComenziRepository $comenziRepository, EntityManagerInterface $entityManager): Response
    {
        $comanda = $comenziRepository->find($comanda_id);
        if (!$comanda) {
            throw $this->createNotFoundException('Comanda nu a fost găsită');
        }

        $retur = new Retururi();
        $retur->setComanda($comanda);
        $form = $this->createForm(RetururiType::class, $retur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($retur);
            $entityManager->flush();
            $comanda = $retur->getComanda();
            $comanda->calculateAndSetProfit();
            $entityManager->flush();
            return $this->redirectToRoute('app_comenzi_show', ['id' => $comanda->getId()]);
        }

        return $this->render('retururi/new.html.twig', [
            'form' => $form->createView(),
            'comanda' => $comanda,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_retururi_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Retururi $retur, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RetururiType::class, $retur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $comanda = $retur->getComanda();
            $comanda->calculateAndSetProfit();
            $entityManager->flush();
            return $this->redirectToRoute('app_comenzi_show', ['id' => $retur->getComanda()->getId()]);
        }

        return $this->render('retururi/edit.html.twig', [
            'form' => $form->createView(),
            'retur' => $retur,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_retururi_delete', methods: ['POST'])]
    public function delete(Request $request, Retururi $retur, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $retur->getId(), $request->request->get('_token'))) {
            $comandaId = $retur->getComanda()->getId();
            $entityManager->remove($retur);
            $entityManager->flush();
            $comanda = $retur->getComanda();
            $comanda->calculateAndSetProfit();
            $entityManager->flush();
            return $this->redirectToRoute('app_comenzi_show', ['id' => $comandaId]);
        }

        return $this->redirectToRoute('app_comenzi_show', ['id' => $retur->getComanda()->getId()]);
    }

    #[Route('/{id}/update-facturat', name: 'app_retururi_update_facturat', methods: ['POST'])]
    public function updateFacturat(Request $request, Retururi $retur, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            if (!$retur) {
                throw $this->createNotFoundException('Returul nu a fost găsit');
            }

            $facturat = filter_var($request->request->get('facturat', false), FILTER_VALIDATE_BOOLEAN);
            $retur->setFacturat($facturat);
            $entityManager->flush();

            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}