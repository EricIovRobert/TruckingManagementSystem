<?php

namespace App\Controller;

use App\Entity\ComenziComunitare;
use App\Entity\Cheltuieli;
use App\Form\CheltuieliType;
use App\Entity\Consumabile;
use App\Entity\SubcategoriiCheltuieli;
use App\Form\ComenziComunitareType;
use App\Repository\ComenziComunitareRepository;
use App\Repository\ParcAutoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/comenzi/comunitare')]
class ComenziComunitareController extends AbstractController
{
    #[Route('/', name: 'app_comenzi_comunitare_index', methods: ['GET'])]
public function index(
    Request $request,
    ComenziComunitareRepository $comenziComunitareRepository,
    PaginatorInterface $paginator
): Response {
    $startDate = $request->query->get('start_date');
    $formattedDate = null;

    if ($startDate) {
        // Împarte data în zi, lună, an (format dd/mm/yyyy)
        $dateParts = explode('/', $startDate);
        if (count($dateParts) === 3) {
            // Recompune în format Y-m-d
            $formattedDate = $dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0];
        }
    }

    $queryBuilder = $comenziComunitareRepository->createQueryBuilder('cc');

    if ($formattedDate) {
        $queryBuilder->andWhere('cc.data_start = :formattedDate') // Corectat din dataStart în data_start
                     ->setParameter('formattedDate', new \DateTime($formattedDate));
    }

    // Ordonare descrescătoare implicită după ID
    $queryBuilder->orderBy('cc.id', 'DESC');

    $comenzi = $queryBuilder->getQuery()->getResult();

    // Paginare
    $pagination = $paginator->paginate(
        $comenzi,
        $request->query->getInt('page', 1),
        15 // 10 comenzi pe pagină
    );

    return $this->render('comenzi_comunitare/index.html.twig', [
        'pagination' => $pagination,
        'start_date' => $startDate,
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

        // Adăugăm cheltuielile pentru consumabile și recalculăm profitul
        $this->addConsumabileCheltuieli($comenziComunitare, $entityManager);

        return $this->redirectToRoute('app_comenzi_comunitare_index', [], Response::HTTP_SEE_OTHER);
    }

    $parcAutoList = $parcAutoRepository->findAll();

    return $this->render('comenzi_comunitare/new.html.twig', [
        'form' => $form->createView(),
        'parc_auto_list' => $parcAutoList,
    ]);
}

    #[Route('/{id}', name: 'app_comenzi_comunitare_show', methods: ['GET'])]
    public function show(ComenziComunitare $comenziComunitare, ParcAutoRepository $parcAutoRepository): Response
    {
        return $this->render('comenzi_comunitare/show.html.twig', [
            'comanda' => $comenziComunitare,
            'parc_autos' => $parcAutoRepository->findAll(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_comenzi_comunitare_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ComenziComunitare $comenziComunitare, EntityManagerInterface $entityManager, ParcAutoRepository $parcAutoRepository): Response
    {
        $oldNrKm = $comenziComunitare->getNrKm();
        $oldDataStop = $comenziComunitare->getDataStop();
    
        $form = $this->createForm(ComenziComunitareType::class, $comenziComunitare);
        $form->get('nr_auto')->setData($comenziComunitare->getNrAuto() ? $comenziComunitare->getNrAuto()->getNrAuto() : '');
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
    
            $newNrKm = $comenziComunitare->getNrKm();
            $newDataStop = $comenziComunitare->getDataStop();
            if ($oldNrKm !== $newNrKm || $oldDataStop !== $newDataStop) {
                // Ștergem cheltuielile existente care au un consumabil setat
                foreach ($comenziComunitare->getCheltuielis() as $cheltuiala) {
                    if ($cheltuiala->getConsumabil() !== null) {
                        $entityManager->remove($cheltuiala);
                    }
                }
                $entityManager->flush();
    
                // Adăugăm noile cheltuieli pentru consumabile și recalculăm profitul
                $this->addConsumabileCheltuieli($comenziComunitare, $entityManager);
            }
    
            return $this->redirectToRoute('app_comenzi_comunitare_show', ['id' => $comenziComunitare->getId()]);
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

    #[Route('/{id}/cheltuieli/new', name: 'app_comenzi_comunitare_cheltuieli_new', methods: ['GET', 'POST'])]
public function newCheltuiala(Request $request, ComenziComunitare $comanda, EntityManagerInterface $entityManager): Response
{
    $cheltuiala = new Cheltuieli();
    $cheltuiala->setComunitar($comanda);
    // Setăm data implicit la data_stop sau data_start
    $cheltuiala->setDataCheltuiala($comanda->getDataStop() ?? $comanda->getDataStart());

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

                    $numarKm = $comanda->getNrKm();
                    $pretMaxim = $consumabil->getPretMaxim();
                    $kmUtilizareMax = $consumabil->getKmUtilizareMax();

                    if ($numarKm !== null && $kmUtilizareMax !== null && $kmUtilizareMax > 0) {
                        $sumaProportionala = ($pretMaxim / $kmUtilizareMax) * $numarKm;
                        $cheltuiala->setSuma($sumaProportionala);
                    } else {
                        $cheltuiala->setSuma($pretMaxim);
                    }
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
        $comanda->calculateAndSetProfit();
        $entityManager->flush();

        return $this->redirectToRoute('app_comenzi_comunitare_show', ['id' => $comanda->getId()]);
    }

    return $this->render('comenzi_comunitare/cheltuieli_new.html.twig', [
        'form' => $form->createView(),
        'comanda' => $comanda,
    ]);
}

    #[Route('/{comandaId}/cheltuieli/{cheltuialaId}/edit', name: 'app_comenzi_comunitare_cheltuieli_edit', methods: ['GET', 'POST'])]
    public function editCheltuiala(
        Request $request,
        int $comandaId,
        int $cheltuialaId,
        EntityManagerInterface $entityManager
    ): Response {
        $comanda = $entityManager->getRepository(ComenziComunitare::class)->find($comandaId);
        if (!$comanda) {
            throw $this->createNotFoundException('Comanda nu a fost găsită.');
        }

        $cheltuiala = $entityManager->getRepository(Cheltuieli::class)->find($cheltuialaId);
        if (!$cheltuiala || $cheltuiala->getComunitar() !== $comanda) {
            throw $this->createNotFoundException('Cheltuiala nu a fost găsită pentru această comandă.');
        }

        $form = $this->createForm(CheltuieliType::class, $cheltuiala);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cheltuiala->setConsumabil(null);
            $cheltuiala->setSubcategorie(null);

            $categorie = $cheltuiala->getCategorie();
            $subcategorieId = $form->get('subcategorie')->getData();

            if ($categorie && $categorie->getNume() === 'Consumabile') {
                if ($subcategorieId) {
                    $consumabil = $entityManager->getRepository(Consumabile::class)->find($subcategorieId);
                    if ($consumabil) {
                        $cheltuiala->setConsumabil($consumabil);
                        $numarKm = $comanda->getNrKm();
                        $pretMaxim = $consumabil->getPretMaxim();
                        $kmUtilizareMax = $consumabil->getKmUtilizareMax();

                        if ($numarKm !== null && $kmUtilizareMax !== null && $kmUtilizareMax > 0) {
                            $sumaProportionala = ($pretMaxim / $kmUtilizareMax) * $numarKm;
                            $cheltuiala->setSuma($sumaProportionala);
                        } else {
                            $cheltuiala->setSuma($pretMaxim);
                        }
                    }
                }
            } else {
                if ($subcategorieId) {
                    $subcategorie = $entityManager->getRepository(SubcategoriiCheltuieli::class)->find($subcategorieId);
                    if ($subcategorie) {
                        $cheltuiala->setSubcategorie($subcategorie);
                    }
                }
            }

            $entityManager->flush();
            $comanda->calculateAndSetProfit(); // Assuming you'll add this method
            $entityManager->flush();

            return $this->redirectToRoute('app_comenzi_comunitare_show', ['id' => $comandaId]);
        }

        return $this->render('comenzi_comunitare/cheltuieli_edit.html.twig', [
            'comanda' => $comanda,
            'cheltuiala' => $cheltuiala,
            'form' => $form->createView(),
        ]);
    }

    private function addConsumabileCheltuieli(ComenziComunitare $comanda, EntityManagerInterface $entityManager): void
{
    $numarKm = $comanda->getNrKm();
    if ($numarKm === null) {
        return;
    }

    $consumabile = $entityManager->getRepository(Consumabile::class)->findAll();
    $dataCheltuiala = $comanda->getDataStop() ?? $comanda->getDataStart();

    foreach ($consumabile as $consumabil) {
        $cheltuiala = new Cheltuieli();
        $cheltuiala->setComunitar($comanda);
        $cheltuiala->setCategorie($consumabil->getCategorie());
        $cheltuiala->setConsumabil($consumabil);
        $cheltuiala->setSubcategorie(null);
        $cheltuiala->setDataCheltuiala($dataCheltuiala); // Setăm data_stop sau data_start

        $pretMaxim = $consumabil->getPretMaxim();
        $kmUtilizareMax = $consumabil->getKmUtilizareMax();

        if ($kmUtilizareMax !== null && $kmUtilizareMax > 0) {
            $sumaProportionala = ($pretMaxim / $kmUtilizareMax) * $numarKm;
        } else {
            $sumaProportionala = $pretMaxim;
        }

        $cheltuiala->setSuma($sumaProportionala);

        $entityManager->persist($cheltuiala);
        $comanda->addCheltuieli($cheltuiala);
    }

    $comanda->calculateAndSetProfit();
    $entityManager->flush();
}
    #[Route('/{comandaId}/cheltuieli/{cheltuialaId}/delete', name: 'app_comenzi_comunitare_cheltuieli_delete', methods: ['POST'])]
    public function deleteCheltuiala(
        Request $request,
        int $comandaId,
        int $cheltuialaId,
        EntityManagerInterface $entityManager
    ): Response {
        $comanda = $entityManager->getRepository(ComenziComunitare::class)->find($comandaId);
        if (!$comanda) {
            throw $this->createNotFoundException('Comanda nu a fost găsită.');
        }

        $cheltuiala = $entityManager->getRepository(Cheltuieli::class)->find($cheltuialaId);
        if (!$cheltuiala || $cheltuiala->getComunitar() !== $comanda) {
            throw $this->createNotFoundException('Cheltuiala nu a fost găsită pentru această comandă.');
        }

        if ($this->isCsrfTokenValid('delete'.$cheltuiala->getId(), $request->request->get('_token'))) {
            $entityManager->remove($cheltuiala);
            $entityManager->flush();
            $comanda->calculateAndSetProfit(); // Assuming you'll add this method
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_comenzi_comunitare_show', ['id' => $comandaId]);
    }

    #[Route('/{id}/update-calculata', name: 'app_comenzi_comunitare_update_calculata', methods: ['POST'])]
    public function updateCalculata(Request $request, ComenziComunitare $comanda, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            if (!$comanda) {
                throw $this->createNotFoundException('Comanda nu a fost găsită');
            }

            $calculata = filter_var($request->request->get('calculata', false), FILTER_VALIDATE_BOOLEAN);
            $comanda->setCalculata($calculata);
            $entityManager->flush();

            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}