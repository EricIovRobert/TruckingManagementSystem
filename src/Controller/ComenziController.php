<?php

namespace App\Controller;

use App\Entity\Comenzi;
use App\Form\ComenziType;
use App\Repository\ComenziRepository;
use App\Repository\ParcAutoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/comenzi')]
class ComenziController extends AbstractController
{
    #[Route('/', name: 'app_comenzi_index')]
    public function index(ComenziRepository $comenziRepository): Response
    {
        $comenzi = $comenziRepository->findAll();

        return $this->render('comenzi/index.html.twig', [
            'comenzi' => $comenzi,
        ]);
    }

    #[Route('/new', name: 'app_comenzi_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ParcAutoRepository $parcAutoRepository): Response
    {
        $comanda = new Comenzi();
        $form = $this->createForm(ComenziType::class, $comanda);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($comanda);
            $entityManager->flush();
            return $this->redirectToRoute('app_comenzi_index');
        }

        return $this->render('comenzi/new.html.twig', [
            'form' => $form->createView(),
            'parc_auto_list' => $parcAutoRepository->findAll(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_comenzi_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Comenzi $comanda, EntityManagerInterface $entityManager, ParcAutoRepository $parcAutoRepository): Response
    {
        $form = $this->createForm(ComenziType::class, $comanda);
        $form->get('parcAutoNr')->setData($comanda->getParcAuto() ? $comanda->getParcAuto()->getNrAuto() : '');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_comenzi_index');
        }

        return $this->render('comenzi/edit.html.twig', [
            'form' => $form->createView(),
            'comanda' => $comanda,
            'parc_auto_list' => $parcAutoRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'app_comenzi_delete', methods: ['POST'])]
    public function delete(Request $request, Comenzi $comanda, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $comanda->getId(), $request->request->get('_token'))) {
            $entityManager->remove($comanda);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_comenzi_index');
    }

    #[Route('/{id}/show', name: 'app_comenzi_show', methods: ['GET'])]
    public function show(Comenzi $comanda, ParcAutoRepository $parcAutoRepository): Response
    {
        return $this->render('comenzi/show.html.twig', [
            'comanda' => $comanda,
            'parc_autos' => $parcAutoRepository->findAll(),
        ]);
    }

    #[Route('/{id}/update-rezolvat', name: 'app_comenzi_update_rezolvat', methods: ['POST'])]
    public function updateRezolvat(Request $request, Comenzi $comanda, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            if (!$comanda) {
                throw $this->createNotFoundException('Comanda nu a fost găsită');
            }

            $rezolvat = filter_var($request->request->get('rezolvat', false), FILTER_VALIDATE_BOOLEAN);
            $comanda->setRezolvat($rezolvat);
            $entityManager->flush();

            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/{id}/update-observatii', name: 'app_comenzi_update_observatii', methods: ['POST'])]
    public function updateObservatii(Request $request, Comenzi $comanda, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            if (!$comanda) {
                throw $this->createNotFoundException('Comanda nu a fost găsită');
            }

            $observatii = $request->request->get('observatii');
            $comanda->setObservatii($observatii);
            $entityManager->flush();

            return new JsonResponse(['success' => true, 'observatii' => $observatii]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/{id}/update-nr-accident-auto', name: 'app_comenzi_update_nr_accident_auto', methods: ['POST'])]
    public function updateNrAccidentAuto(Request $request, Comenzi $comanda, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            if (!$comanda) {
                throw $this->createNotFoundException('Comanda nu a fost găsită');
            }

            $nrAccidentAuto = $request->request->get('nrAccidentAuto');
            $comanda->setNrAccidentAuto($nrAccidentAuto);
            $entityManager->flush();

            return new JsonResponse(['success' => true, 'nrAccidentAuto' => $nrAccidentAuto ?: 'N/A']);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}