<?php

namespace App\Controller;

use App\Entity\Comenzi;
use App\Entity\Cheltuieli;
use App\Form\CheltuieliType;
use App\Form\ComenziType;
use App\Repository\ComenziRepository;
use App\Repository\ParcAutoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\SubcategoriiCheltuieli;
use App\Entity\Consumabile;
use App\Entity\CategoriiCheltuieli;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/comenzi')]
class ComenziController extends AbstractController
{
    #[Route('/', name: 'app_comenzi_index')]
    public function index(
        Request $request,
        ComenziRepository $comenziRepository,
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator
    ): Response {
        $search = $request->query->get('search');
        $startDate = $request->query->get('start_date');
        $sortBy = $request->query->get('sort_by');
    
        // Adăugăm parametrul pentru filtrul rezolvat
        $rezolvat = $request->query->get('rezolvat');
    
        $queryBuilder = $comenziRepository->createQueryBuilder('c');
        if ($search) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    'c.parcAutoNrSnapshot LIKE :search',
                    'c.nrAccidentAuto LIKE :search',
                    'c.sofer LIKE :search',
                    'c.observatii LIKE :search',
                    $queryBuilder->expr()->exists(
                        $entityManager->createQueryBuilder()
                            ->select('t.id')
                            ->from('App\Entity\Tururi', 't')
                            ->where('t.comanda = c.id')
                            ->andWhere('t.rutaIncarcare LIKE :search OR t.rutaDescarcare LIKE :search')
                    ),
                    $queryBuilder->expr()->exists(
                        $entityManager->createQueryBuilder()
                            ->select('r.id')
                            ->from('App\Entity\Retururi', 'r')
                            ->where('r.comanda = c.id')
                            ->andWhere('r.rutaIncarcare LIKE :search OR r.rutaDescarcare LIKE :search')
                    )
                )
            )
            ->setParameter('search', '%' . $search . '%');
        }
    
        if ($startDate) {
            $queryBuilder->andWhere('c.dataStart >= :startDate')
                         ->setParameter('startDate', new \DateTime($startDate));
        }
    
        // Aplicăm filtrul pentru rezolvat doar dacă este specificat explicit (1 sau 0)
        if ($rezolvat !== null && in_array($rezolvat, ['1', '0'])) {
            $queryBuilder->andWhere('c.rezolvat = :rezolvat')
                         ->setParameter('rezolvat', filter_var($rezolvat, FILTER_VALIDATE_BOOLEAN));
        }
    
        $comenzi = $queryBuilder->getQuery()->getResult();
    
        usort($comenzi, function ($a, $b) use ($sortBy) {
            // Dacă nu s-a selectat niciun filtru, se sortează descrescător după ID (implicit: ordine inversă adăugării)
            if (empty($sortBy)) {
                return $b->getId() <=> $a->getId();
            } elseif ($sortBy === 'profit_desc') {
                return ($b->getProfit() ?? 0) <=> ($a->getProfit() ?? 0);
            } elseif ($sortBy === 'profit_asc') {
                return ($a->getProfit() ?? 0) <=> ($b->getProfit() ?? 0);
            } elseif ($sortBy === 'consum_desc') {
                $consumA = $this->calculateConsum($a);
                $consumB = $this->calculateConsum($b);
                return $consumB <=> $consumA;
            } elseif ($sortBy === 'consum_asc') {
                $consumA = $this->calculateConsum($a);
                $consumB = $this->calculateConsum($b);
                return $consumA <=> $consumB;
            } elseif ($sortBy === 'pret_km_desc') {
                $pretKmA = $this->calculatePretPerKm($a);
                $pretKmB = $this->calculatePretPerKm($b);
                return $pretKmB <=> $pretKmA;
            } elseif ($sortBy === 'pret_km_asc') {
                $pretKmA = $this->calculatePretPerKm($a);
                $pretKmB = $this->calculatePretPerKm($b);
                return $pretKmA <=> $pretKmB;
            }
            return 0;
        });
    
        $pagination = $paginator->paginate(
            $comenzi,
            $request->query->getInt('page', 1),
            10
        );
    
        return $this->render('comenzi/index.html.twig', [
            'pagination' => $pagination,
            'search' => $search,
            'start_date' => $startDate,
            'sort_by' => $sortBy,
            'rezolvat' => $rezolvat,
        ]);
    }
    
    private function calculateConsum(Comenzi $comanda): float
    {
        $consum = 0;
        foreach ($comanda->getTururis() as $tur) {
            $consum += $tur->getKg() ?? 0;
        }
        foreach ($comanda->getRetururis() as $retur) {
            $consum += $retur->getKg() ?? 0;
        }
        return $consum;
    }
    
    private function calculatePretPerKm(Comenzi $comanda): float
    {
        $totalPret = 0;
        foreach ($comanda->getTururis() as $tur) {
            $totalPret += $tur->getPret() ?? 0;
        }
        foreach ($comanda->getRetururis() as $retur) {
            $totalPret += $retur->getPret() ?? 0;
        }
        return ($comanda->getNumarKm() > 0) ? ($totalPret / $comanda->getNumarKm()) : 0;
    }

    private function addConsumabileCheltuieli(Comenzi $comanda, EntityManagerInterface $entityManager): void
{
    $numarKm = $comanda->getNumarKm();
    if ($numarKm === null) {
        return;
    }

    $consumabile = $entityManager->getRepository(Consumabile::class)->findAll();

    foreach ($consumabile as $consumabil) {
        $cheltuiala = new Cheltuieli();
        $cheltuiala->setComanda($comanda);
        $cheltuiala->setCategorie($consumabil->getCategorie());
        $cheltuiala->setConsumabil($consumabil);
        $cheltuiala->setSubcategorie(null);
        $cheltuiala->setDataCheltuiala(new \DateTime());

        $pretMaxim = $consumabil->getPretMaxim();
        $kmUtilizareMax = $consumabil->getKmUtilizareMax();

        if ($kmUtilizareMax !== null && $kmUtilizareMax > 0) {
            $sumaProportionala = ($pretMaxim / $kmUtilizareMax) * $numarKm;
        } else {
            $sumaProportionala = $pretMaxim;
        }

        $cheltuiala->setSuma($sumaProportionala);

        // Persistăm manual entitatea
        $entityManager->persist($cheltuiala);

        $comanda->addCheltuieli($cheltuiala);
    }

    $comanda->calculateAndSetProfit();
    $entityManager->flush();
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

        // Adăugăm cheltuielile pentru consumabile și recalculăm profitul
        $this->addConsumabileCheltuieli($comanda, $entityManager);

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
    $oldNumarKm = $comanda->getNumarKm();

    $form = $this->createForm(ComenziType::class, $comanda);
    $form->get('parcAutoNr')->setData($comanda->getParcAuto() ? $comanda->getParcAuto()->getNrAuto() : '');
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->flush();

        $newNumarKm = $comanda->getNumarKm();
        if ($oldNumarKm !== $newNumarKm) {
            // Ștergem cheltuielile existente care au un consumabil setat
            foreach ($comanda->getCheltuielis() as $cheltuiala) {
                if ($cheltuiala->getConsumabil() !== null) {
                    $entityManager->remove($cheltuiala);
                }
            }
            $entityManager->flush();

            // Adăugăm noile cheltuieli pentru consumabile și recalculăm profitul
            $this->addConsumabileCheltuieli($comanda, $entityManager);
        }

        return $this->redirectToRoute('app_comenzi_show', ['id' => $comanda->getId()]);
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
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/{id}/cheltuieli/new', name: 'app_comenzi_cheltuieli_new', methods: ['GET', 'POST'])]
    public function newCheltuiala(Request $request, Comenzi $comanda, EntityManagerInterface $entityManager): Response
    {
        $cheltuiala = new Cheltuieli();
        $cheltuiala->setComanda($comanda);
        $cheltuiala->setDataCheltuiala(new \DateTime());

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

                        $numarKm = $comanda->getNumarKm();
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

            return $this->redirectToRoute('app_comenzi_show', ['id' => $comanda->getId()]);
        }

        return $this->render('comenzi/cheltuieli_new.html.twig', [
            'form' => $form->createView(),
            'comanda' => $comanda,
        ]);
    }

    #[Route('/{comandaId}/cheltuieli/{cheltuialaId}/edit', name: 'app_comenzi_cheltuieli_edit', methods: ['GET', 'POST'])]
    public function editCheltuiala(
        Request $request,
        int $comandaId,
        int $cheltuialaId,
        EntityManagerInterface $entityManager
    ): Response {
        $comanda = $entityManager->getRepository(Comenzi::class)->find($comandaId);
        if (!$comanda) {
            throw $this->createNotFoundException('Comanda nu a fost găsită.');
        }

        $cheltuiala = $entityManager->getRepository(Cheltuieli::class)->find($cheltuialaId);
        if (!$cheltuiala || $cheltuiala->getComanda() !== $comanda) {
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
                        $numarKm = $comanda->getNumarKm();
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
            $comanda->calculateAndSetProfit();
            $entityManager->flush();

            return $this->redirectToRoute('app_comenzi_show', ['id' => $comandaId]);
        }

        return $this->render('comenzi/cheltuieli_edit.html.twig', [
            'comanda' => $comanda,
            'cheltuiala' => $cheltuiala,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{comandaId}/cheltuieli/{cheltuialaId}/delete', name: 'app_comenzi_cheltuieli_delete', methods: ['POST'])]
    public function deleteCheltuiala(
        Request $request,
        int $comandaId,
        int $cheltuialaId,
        EntityManagerInterface $entityManager
    ): Response {
        $comanda = $entityManager->getRepository(Comenzi::class)->find($comandaId);
        if (!$comanda) {
            throw $this->createNotFoundException('Comanda nu a fost găsită.');
        }

        $cheltuiala = $entityManager->getRepository(Cheltuieli::class)->find($cheltuialaId);
        if (!$cheltuiala || $cheltuiala->getComanda() !== $comanda) {
            throw $this->createNotFoundException('Cheltuiala nu a fost găsită pentru această comandă.');
        }

        if ($this->isCsrfTokenValid('delete'.$cheltuiala->getId(), $request->request->get('_token'))) {
            $entityManager->remove($cheltuiala);
            $entityManager->flush();
            $comanda->calculateAndSetProfit();
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_comenzi_show', ['id' => $comandaId]);
    }

    #[Route('/get-subcategories', name: 'app_get_subcategories', methods: ['GET'])]
    public function getSubcategories(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $categorieId = $request->query->get('categorie');
        if (!$categorieId) {
            return new JsonResponse([]);
        }

        $categorie = $entityManager->getRepository(CategoriiCheltuieli::class)->find($categorieId);
        if (!$categorie) {
            return new JsonResponse([]);
        }

        if ($categorie->getNume() === 'Consumabile') {
            $consumabile = $entityManager->getRepository(Consumabile::class)
                ->findBy(['categorie' => $categorieId]);

            $data = array_map(function ($consumabil) {
                return [
                    'id' => $consumabil->getId(),
                    'nume' => $consumabil->getNume(),
                    'pret_standard' => $consumabil->getPretMaxim(),
                    'km_utilizare_max' => $consumabil->getKmUtilizareMax(),
                    'pret_per_l' => null,
                ];
            }, $consumabile);
        } else {
            $subcategorii = $entityManager->getRepository(SubcategoriiCheltuieli::class)
                ->findBy(['categorie' => $categorieId]);

            $data = array_map(function ($subcategorie) {
                return [
                    'id' => $subcategorie->getId(),
                    'nume' => $subcategorie->getNume(),
                    'pret_standard' => $subcategorie->getPretStandard(),
                    'km_utilizare_max' => null,
                    'pret_per_l' => $subcategorie->getPretPerL(),
                ];
            }, $subcategorii);
        }

        return new JsonResponse($data);
    }
}