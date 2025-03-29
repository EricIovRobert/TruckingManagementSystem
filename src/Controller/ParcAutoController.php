<?php

namespace App\Controller;

use App\Entity\ParcAuto;
use App\Form\ParcAutoType;
use App\Repository\ComenziRepository; // Adăugat pentru a folosi ComenziRepository
use App\Repository\ParcAutoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/parc-auto')]
class ParcAutoController extends AbstractController
{
    #[Route('/', name: 'app_parc_auto_index', methods: ['GET'])]
    public function index(
        Request $request,
        ParcAutoRepository $parcAutoRepository,
        PaginatorInterface $paginator // Injectăm PaginatorInterface
    ): Response {
        $query = $parcAutoRepository->createQueryBuilder('pa')->getQuery();

        // Paginare cu 10 elemente pe pagină
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1), // Numărul paginii curente, implicit 1
            15 // Limita de 10 elemente pe pagină
        );

        return $this->render('parc_auto/index.html.twig', [
            'pagination' => $pagination, // Transmitem obiectul de paginare către șablon
        ]);
    }

    #[Route('/new', name: 'app_parc_auto_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $parcAuto = new ParcAuto();
        $form = $this->createForm(ParcAutoType::class, $parcAuto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($parcAuto);
            $entityManager->flush();
            return $this->redirectToRoute('app_parc_auto_index');
        }

        return $this->render('parc_auto/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_parc_auto_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ParcAuto $parcAuto, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ParcAutoType::class, $parcAuto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_parc_auto_index');
        }

        return $this->render('parc_auto/edit.html.twig', [
            'form' => $form->createView(),
            'parc_auto' => $parcAuto,
        ]);
    }

    #[Route('/{id}', name: 'app_parc_auto_delete', methods: ['POST'])]
    public function delete(Request $request, ParcAuto $parcAuto, ComenziRepository $comenziRepository, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $parcAuto->getId(), $request->request->get('_token'))) {
            // Verifică dacă mașina este utilizată în comenzi
            $comenziAsociate = $comenziRepository->findBy(['parcAuto' => $parcAuto]);

            if (!empty($comenziAsociate)) {
                // Dacă există comenzi asociate, afișează un mesaj de eroare
                $this->addFlash('error', 'Nu se poate șterge, mașina este legata de o comanda.');
                return $this->redirectToRoute('app_parc_auto_index');
            }

            // Dacă nu există comenzi asociate, șterge mașina
            $entityManager->remove($parcAuto);
            $entityManager->flush();

            $this->addFlash('success', 'Mașina a fost ștearsă cu succes.');
        }

        return $this->redirectToRoute('app_parc_auto_index');
    }
}