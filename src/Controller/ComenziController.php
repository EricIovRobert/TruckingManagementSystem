<?php

namespace App\Controller;

use App\Entity\Comenzi;
use App\Form\ComenziType;
use App\Repository\ComenziRepository;
use App\Repository\ParcAutoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/comenzi')]
class ComenziController extends AbstractController
{
    #[Route('/', name: 'app_comenzi_index')]
    public function index(ComenziRepository $comenziRepository): Response
    {
        // Preia toate înregistrările din tabelul "comenzi"
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
            'app' => ['parc_auto_list' => $parcAutoRepository->findAll()], // Lista pentru datalist
        ]);
    }



    #[Route('/{id}/edit', name: 'app_comenzi_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Comenzi $comanda, EntityManagerInterface $entityManager, ParcAutoRepository $parcAutoRepository): Response
    {
        $form = $this->createForm(ComenziType::class, $comanda);
        $form->get('parcAutoNr')->setData($comanda->getParcAuto() ? $comanda->getParcAuto()->getNrAuto() : ''); // Pre-populare
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_comenzi_index');
        }

        return $this->render('comenzi/edit.html.twig', [
            'form' => $form->createView(),
            'comanda' => $comanda,
            'app' => ['parc_auto_list' => $parcAutoRepository->findAll()], // Lista pentru datalist
        ]);
    }

    #[Route('/{id}', name: 'app_comenzi_delete', methods: ['POST'])]
    public function delete(Request $request, Comenzi $comanda, EntityManagerInterface $entityManager): Response
    {
        // Verifică token-ul CSRF pentru securitate
        if ($this->isCsrfTokenValid('delete'.$comanda->getId(), $request->request->get('_token'))) {
            $entityManager->remove($comanda); // Marchează entitatea pentru ștergere
            $entityManager->flush();          // Aplică ștergerea în baza de date
        }
    
        return $this->redirectToRoute('app_comenzi_index');
    }
    #[Route('/{id}/show', name: 'app_comenzi_show', methods: ['GET'])]
    public function show(Comenzi $comanda): Response
    {
        return $this->render('comenzi/show.html.twig', [
            'comanda' => $comanda,
        ]);
    }
}