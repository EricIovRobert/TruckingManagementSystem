<?php

namespace App\Controller;

use App\Entity\Cheltuieli;
use App\Entity\Consumabile;
use App\Entity\SubcategoriiCheltuieli;
use App\Form\CheltuieliType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface; // <- asigură-te că ai acest use

#[Route('/rapoarte')]
class CheltuieliController extends AbstractController
{
    /**
     * Afișare listă Cheltuieli + FILTRE și PAGINAȚIE
     */
    #[Route('/cheltuieli', name: 'app_cheltuieli_list', methods: ['GET'])]
    public function listCheltuieli(
        EntityManagerInterface $em,
        Request $request,
        PaginatorInterface $paginator
    ): Response {
        // 1) Construim un QueryBuilder pentru a aplica filtrele
        $qb = $em->getRepository(Cheltuieli::class)->createQueryBuilder('ch');

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
            // ex. '3' devine '03' pentru comparare
            $luna = str_pad($luna, 2, '0', STR_PAD_LEFT);
        }

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
                // Dacă avem și an, căutăm luna în anul respectiv
                $startDate = new \DateTime("$an-$luna-01 00:00:00");
                $endDate   = (clone $startDate)->modify('last day of this month')->setTime(23, 59, 59);

                $qb->andWhere('ch.data_cheltuiala >= :s2')
                   ->andWhere('ch.data_cheltuiala <= :e2')
                   ->setParameter('s2', $startDate)
                   ->setParameter('e2', $endDate);
            } else {
                // Dacă nu avem an, căutăm luna X în toți anii din BD
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

        // Filtru tip (comenzi / comunitare / fara_comanda)
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

        // Ordonăm descendent după data + ID (ca să apară în ordinea introducerii invers)
        $qb->orderBy('ch.data_cheltuiala', 'DESC')
           ->addOrderBy('ch.id', 'DESC');

        // 2) Aplicăm paginația
        $query      = $qb->getQuery();
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1), 
            10 // câte rezultate vrei pe pagină
        );

        // 3) Luăm itemele paginii curente
        $cheltuieli = $pagination->getItems();

        // 4) (Opțional) încărcăm categoriile pentru select (dacă vrei filtru categorie)
        $categorii = $em->getRepository('App\Entity\CategoriiCheltuieli')->findAll();

        // AICI păstrăm EXACT ce era înainte, DOAR că îi trimitem $cheltuieli filtrate
        // + adăugăm 'pagination', 'an', 'luna', etc. pentru twig (dacă vrei).
        return $this->render('cheltuieli/index.html.twig', [
            'cheltuieli' => $cheltuieli,        // tabloul filtrat + paginat
            'pagination' => $pagination,        // obiectul de paginare (ca să-l poți afișa)
            // Filtre curente (dacă le vrei în twig):
            'an'         => $an,
            'luna'       => $luna,
            'type'       => $type,
            'categorie'  => $categorieId,
            'categorii'  => $categorii,
        ]);
    }

    /**
     * Creare Cheltuială (păstrat exact cum era).
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
     * Editare Cheltuială (păstrat exact cum era).
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
     * Ștergere Cheltuială (păstrat exact cum era).
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

    /**
     * Exemplu: buton "Vezi Rapoarte" (placeholder).
     * Poți face ce vrei aici mai târziu.
     */
    #[Route('/cheltuieli/raport', name: 'app_cheltuieli_raport', methods: ['GET'])]
    public function raportPlaceholder(): Response
    {
        // Deocamdată doar un text simplu.
        return new Response('<h1>Pagina de Rapoarte (viitor).</h1>');
    }
}
