<?php

namespace App\Controller;

use App\Entity\Cheltuieli;
use App\Entity\Consumabile;
use App\Entity\SubcategoriiCheltuieli;
use App\Form\CheltuieliType;
use App\Entity\CategoriiCheltuieli;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/rapoarte')]
class CheltuieliController extends AbstractController
{
    /**
     * Afișare listă Cheltuieli cu filtre, paginare și total
     */
    #[Route('/cheltuieli', name: 'app_cheltuieli_list', methods: ['GET'])]
    public function listCheltuieli(
        EntityManagerInterface $em,
        Request $request,
        PaginatorInterface $paginator
    ): Response {
        // Parametrii din GET
        $an         = $request->query->get('an');
        $luna       = $request->query->get('luna');
        $type       = $request->query->get('type');
        $categorieId = $request->query->get('categorie');

        // Validare an
        $anValid = false;
        if ($an && preg_match('/^\d{4}$/', $an)) {
            $anValid = true;
        }

        // Validare lună (1-12)
        $lunaValid = false;
        if ($luna && in_array($luna, range(1, 12))) {
            $lunaValid = true;
            $luna = str_pad($luna, 2, '0', STR_PAD_LEFT);
        }

        // Construim QueryBuilder pentru lista de cheltuieli
        $qb = $em->getRepository(Cheltuieli::class)->createQueryBuilder('ch');

        // Filtru an
        if ($anValid) {
            $startDate = new \DateTime("$an-01-01 00:00:00");
            $endDate   = new \DateTime("$an-12-31 23:59:59");
            $qb->andWhere('ch.data_cheltuiala >= :startDate')
               ->andWhere('ch.data_cheltuiala <= :endDate')
               ->setParameter('startDate', $startDate)
               ->setParameter('endDate', $endDate);
        }

        // Filtru lună
        if ($lunaValid) {
            if ($anValid) {
                $startDate = new \DateTime("$an-$luna-01 00:00:00");
                $endDate   = (clone $startDate)->modify('last day of this month')->setTime(23, 59, 59);
                $qb->andWhere('ch.data_cheltuiala >= :s2')
                   ->andWhere('ch.data_cheltuiala <= :e2')
                   ->setParameter('s2', $startDate)
                   ->setParameter('e2', $endDate);
            } else {
                $minMax = $em->createQueryBuilder()
                    ->select('MIN(ch.data_cheltuiala) as minDate, MAX(ch.data_cheltuiala) as maxDate')
                    ->from(Cheltuieli::class, 'ch')
                    ->getQuery()
                    ->getSingleResult();
                $minDate = $minMax['minDate'] ? new \DateTime($minMax['minDate']) : new \DateTime();
                $maxDate = $minMax['maxDate'] ? new \DateTime($minMax['maxDate']) : new \DateTime();
                $minYear = (int) $minDate->format('Y');
                $maxYear = (int) $maxDate->format('Y');
                $orX = $qb->expr()->orX();
                for ($year = $minYear; $year <= $maxYear; $year++) {
                    $start = new \DateTime("$year-$luna-01 00:00:00");
                    $end   = (clone $start)->modify('last day of this month')->setTime(23, 59, 59);
                    $orX->add($qb->expr()->andX(
                        $qb->expr()->gte('ch.data_cheltuiala', ":start_$year"),
                        $qb->expr()->lte('ch.data_cheltuiala', ":end_$year")
                    ));
                    $qb->setParameter("start_$year", $start)
                       ->setParameter("end_$year", $end);
                }
                if ($orX->count() > 0) {
                    $qb->andWhere($orX);
                }
            }
        }

        // Filtru tip
        if ($type === 'comenzi') {
            $qb->andWhere('ch.comanda IS NOT NULL')
               ->andWhere('ch.comunitar IS NULL');
        } elseif ($type === 'comunitare') {
            $qb->andWhere('ch.comunitar IS NOT NULL')
               ->andWhere('ch.comanda IS NULL');
        } elseif ($type === 'fara_comanda') {
            $qb->andWhere('ch.comanda IS NULL')
               ->andWhere('ch.comunitar IS NULL');
        }

        // Filtru categorie
        if ($categorieId) {
            $qb->andWhere('ch.categorie = :cat')
               ->setParameter('cat', $categorieId);
        }

        // Clonăm QueryBuilder-ul pentru a calcula totalul ajustat
        $qbTotal = clone $qb;
        $qbTotal->select('SUM(
            CASE
                WHEN ch.tva > 0 THEN
                    ch.suma - (ch.suma * ch.tva / (100 + ch.tva)) + 
                    ((ch.suma * ch.tva / (100 + ch.tva)) * (COALESCE(ch.comision_tva, 0) / 100))
                ELSE
                    ch.suma
            END
        ) as total');

        // Obținem totalul
        $total = $qbTotal->getQuery()->getSingleScalarResult();
        $total = $total ? round($total, 2) : 0;

        // Continuăm cu QueryBuilder-ul original pentru lista de cheltuieli
        $qb->orderBy('ch.data_cheltuiala', 'DESC')
           ->addOrderBy('ch.id', 'DESC');

        $query      = $qb->getQuery();
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        $cheltuieli = $pagination->getItems();
        $categorii = $em->getRepository('App\Entity\CategoriiCheltuieli')->findAll();

        return $this->render('cheltuieli/index.html.twig', [
            'cheltuieli' => $cheltuieli,
            'pagination' => $pagination,
            'an'         => $an,
            'luna'       => $luna,
            'type'       => $type,
            'categorie'  => $categorieId,
            'categorii'  => $categorii,
            'total'      => $total, // Adăugăm totalul în context
        ]);
    }

    /**
     * Creare Cheltuială
     */
    #[Route('/cheltuieli/new', name: 'app_cheltuieli_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $cheltuiala = new Cheltuieli();
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

            return $this->redirectToRoute('app_cheltuieli_list');
        }

        return $this->render('cheltuieli/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Editare Cheltuială
     */
    #[Route('/cheltuieli/{id}/edit', name: 'app_cheltuieli_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Cheltuieli $cheltuiala, EntityManagerInterface $entityManager): Response
    {
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

            $entityManager->flush();

            return $this->redirectToRoute('app_cheltuieli_list');
        }

        return $this->render('cheltuieli/edit.html.twig', [
            'form'       => $form->createView(),
            'cheltuiala' => $cheltuiala,
        ]);
    }

    /**
     * Ștergere Cheltuială
     */
    #[Route('/cheltuieli/{id}/delete', name: 'app_cheltuieli_delete', methods: ['POST'])]
    public function delete(Request $request, Cheltuieli $cheltuiala, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$cheltuiala->getId(), $request->request->get('_token'))) {
            $entityManager->remove($cheltuiala);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_cheltuieli_list');
    }


    #[Route('/cheltuieli/fixe/new', name: 'app_cheltuieli_fixe_new', methods: ['GET', 'POST'])]
public function newFixe(Request $request, EntityManagerInterface $em): Response
{
    $lunaNume = [
        '1' => 'Ianuarie', '2' => 'Februarie', '3' => 'Martie', '4' => 'Aprilie',
        '5' => 'Mai', '6' => 'Iunie', '7' => 'Iulie', '8' => 'August',
        '9' => 'Septembrie', '10' => 'Octombrie', '11' => 'Noiembrie', '12' => 'Decembrie',
    ];

    if ($request->isMethod('POST')) {
        $an = $request->request->get('an');
        $luna = $request->request->get('luna');

        // Validare an și lună
        if (!preg_match('/^\d{4}$/', $an) || !in_array($luna, range(1, 12))) {
            $this->addFlash('error', 'An sau lună invalidă.');
            return $this->redirectToRoute('app_cheltuieli_list');
        }

        // Găsește categoria "Fixe"
        $categorieFixe = $em->getRepository(CategoriiCheltuieli::class)->findOneBy(['nume' => 'Fixe']);
        if (!$categorieFixe) {
            $this->addFlash('error', 'Categoria "Fixe" nu a fost găsită.');
            return $this->redirectToRoute('app_cheltuieli_list');
        }

        // Preia toate subcategoriile din categoria "Fixe"
        $subcategorii = $em->getRepository(SubcategoriiCheltuieli::class)->findBy(['categorie' => $categorieFixe]);

        // Generează lista de cheltuieli fixe pentru revizuire
        $expensesToAdd = [];
        $dataCheltuiala = new \DateTime("$an-" . str_pad($luna, 2, '0', STR_PAD_LEFT) . "-01");
        foreach ($subcategorii as $subcat) {
            $expensesToAdd[] = [
                'subcategorie' => $subcat->getNume(),
                'suma' => $subcat->getPretStandard() ?? 0,
                'data' => $dataCheltuiala->format('Y-m-d'),
                'descriere' => 'Cheltuială fixă pentru ' . 'luna ' . $lunaNume[$luna],
            ];
        }

        // Afișează pagina de revizuire
        return $this->render('cheltuieli/fixe_review.html.twig', [
            'expenses' => $expensesToAdd,
            'an' => $an,
            'luna' => $luna,
            'lunaNume' => $lunaNume,
        ]);
    }

    // Pe GET, afișează formularul de selecție
    return $this->render('cheltuieli/fixe_new.html.twig', [
        'lunaNume' => $lunaNume,
    ]);
}

#[Route('/cheltuieli/fixe/add', name: 'app_cheltuieli_fixe_add', methods: ['POST'])]
public function addFixe(Request $request, EntityManagerInterface $em): Response
{
    $an = $request->request->get('an');
    $luna = $request->request->get('luna');

    // Validare an și lună
    if (!preg_match('/^\d{4}$/', $an) || !in_array($luna, range(1, 12))) {
        $this->addFlash('error', 'An sau lună invalidă.');
        return $this->redirectToRoute('app_cheltuieli_list');
    }

    // Găsește categoria "Fixe"
    $categorieFixe = $em->getRepository(CategoriiCheltuieli::class)->findOneBy(['nume' => 'Fixe']);
    if (!$categorieFixe) {
        $this->addFlash('error', 'Categoria "Fixe" nu a fost găsită.');
        return $this->redirectToRoute('app_cheltuieli_list');
    }

    // Preia toate subcategoriile din categoria "Fixe"
    $subcategorii = $em->getRepository(SubcategoriiCheltuieli::class)->findBy(['categorie' => $categorieFixe]);

    // Creează și salvează cheltuielile
    $dataCheltuiala = new \DateTime("$an-" . str_pad($luna, 2, '0', STR_PAD_LEFT) . "-01");
    foreach ($subcategorii as $subcat) {
        $cheltuiala = new Cheltuieli();
        $cheltuiala->setCategorie($categorieFixe);
        $cheltuiala->setSubcategorie($subcat);
        $cheltuiala->setSuma($subcat->getPretStandard() ?? 0);
        $cheltuiala->setDataCheltuiala($dataCheltuiala);
        $cheltuiala->setDescriere('Cheltuială fixă pentru ' . $subcat->getNume());
        $cheltuiala->setComanda(null); // Fără comandă
        $em->persist($cheltuiala);
    }

    $em->flush();

    $this->addFlash('success', 'Cheltuielile fixe au fost adăugate cu succes.');
    return $this->redirectToRoute('app_cheltuieli_list');
}

 
}