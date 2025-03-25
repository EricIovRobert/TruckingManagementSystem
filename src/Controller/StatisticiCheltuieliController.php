<?php

namespace App\Controller;

use App\Entity\Cheltuieli;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StatisticiCheltuieliController extends AbstractController
{
    #[Route('/statistici/cheltuieli', name: 'app_statistici_cheltuieli', methods: ['GET'])]
    public function statisticiCheltuieli(Request $request, EntityManagerInterface $em): Response
    {
        // Preia parametrii din query (an și lună)
        $an = $request->query->get('an');
        $luna = $request->query->get('luna');

        // Validare an
        $anValid = false;
        if ($an && preg_match('/^\d{4}$/', $an)) {
            $anValid = true;
        }

        // Validare lună (1-12)
        $lunaValid = false;
        if ($luna && in_array($luna, range(1, 12))) {
            $lunaValid = true;
            $luna = str_pad($luna, 2, '0', STR_PAD_LEFT); // Format "01", "02", etc.
        }

        // Construim QueryBuilder pentru suma totală a tuturor cheltuielilor
        $qbTotal = $em->getRepository(Cheltuieli::class)->createQueryBuilder('ch')
            ->select('SUM(
                CASE
                    WHEN ch.tva > 0 THEN
                        ch.suma - (ch.suma * ch.tva / (100 + ch.tva)) + 
                        ((ch.suma * ch.tva / (100 + ch.tva)) * (COALESCE(ch.comision_tva, 0) / 100))
                    ELSE
                        ch.suma
                END
            ) as total');

        // Query separat pentru a obține cheltuielile (nu doar suma)
        $qbCheltuieli = $em->getRepository(Cheltuieli::class)->createQueryBuilder('ch')
            ->select('ch');

        // Filtru an (aplicat ambelor query-uri)
        if ($anValid) {
            $startDate = new \DateTime("$an-01-01 00:00:00");
            $endDate = new \DateTime("$an-12-31 23:59:59");
            $qbTotal->andWhere('ch.data_cheltuiala >= :startDate')
                    ->andWhere('ch.data_cheltuiala <= :endDate')
                    ->setParameter('startDate', $startDate)
                    ->setParameter('endDate', $endDate);
            $qbCheltuieli->andWhere('ch.data_cheltuiala >= :startDate')
                         ->andWhere('ch.data_cheltuiala <= :endDate')
                         ->setParameter('startDate', $startDate)
                         ->setParameter('endDate', $endDate);
        }

        // Filtru lună (aplicat ambelor query-uri)
        if ($lunaValid) {
            if ($anValid) {
                $startDate = new \DateTime("$an-$luna-01 00:00:00");
                $endDate = (clone $startDate)->modify('last day of this month')->setTime(23, 59, 59);
                $qbTotal->andWhere('ch.data_cheltuiala >= :s2')
                        ->andWhere('ch.data_cheltuiala <= :e2')
                        ->setParameter('s2', $startDate)
                        ->setParameter('e2', $endDate);
                $qbCheltuieli->andWhere('ch.data_cheltuiala >= :s2')
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
                $orXTotal = $qbTotal->expr()->orX();
                $orXCheltuieli = $qbCheltuieli->expr()->orX();
                for ($year = $minYear; $year <= $maxYear; $year++) {
                    $start = new \DateTime("$year-$luna-01 00:00:00");
                    $end = (clone $start)->modify('last day of this month')->setTime(23, 59, 59);
                    $orXTotal->add($qbTotal->expr()->andX(
                        $qbTotal->expr()->gte('ch.data_cheltuiala', ":start_$year"),
                        $qbTotal->expr()->lte('ch.data_cheltuiala', ":end_$year")
                    ));
                    $orXCheltuieli->add($qbCheltuieli->expr()->andX(
                        $qbCheltuieli->expr()->gte('ch.data_cheltuiala', ":start_$year"),
                        $qbCheltuieli->expr()->lte('ch.data_cheltuiala', ":end_$year")
                    ));
                    $qbTotal->setParameter("start_$year", $start)
                            ->setParameter("end_$year", $end);
                    $qbCheltuieli->setParameter("start_$year", $start)
                                 ->setParameter("end_$year", $end);
                }
                if ($orXTotal->count() > 0) {
                    $qbTotal->andWhere($orXTotal);
                }
                if ($orXCheltuieli->count() > 0) {
                    $qbCheltuieli->andWhere($orXCheltuieli);
                }
            }
        }

        // Obținem totalul cheltuielilor
        $total = $qbTotal->getQuery()->getSingleScalarResult();
        $total = $total ? round($total, 2) : 0;

        // Obținem cheltuielile filtrate
        $cheltuieli = $qbCheltuieli->getQuery()->getResult();

        // Calculăm sumele pentru comenzi și comunitare, evitând duplicarea
        $totalComenzi = 0.0; // Suma prețurilor tururilor și retururilor
        $totalComunitare = 0.0; // Suma prețurilor comunitare
        $processedComenzi = []; // Array pentru a ține evidența comenzilor procesate
        $processedComunitare = []; // Array pentru a ține evidența comunitarelor procesate

        foreach ($cheltuieli as $cheltuiala) {
            if ($cheltuiala->getComanda() !== null) {
                $comanda = $cheltuiala->getComanda();
                $comandaId = $comanda->getId();
                // Verificăm dacă comanda nu a fost deja procesată și dacă calculata = true
                if (!in_array($comandaId, $processedComenzi) && $comanda->isCalculata()) {
                    foreach ($comanda->getTururis() as $tur) {
                        $totalComenzi += $tur->getPret() ?? 0;
                    }
                    foreach ($comanda->getRetururis() as $retur) {
                        $totalComenzi += $retur->getPret() ?? 0;
                    }
                    $processedComenzi[] = $comandaId; // Marcăm comanda ca procesată
                }
            } elseif ($cheltuiala->getComunitar() !== null) {
                $comunitar = $cheltuiala->getComunitar();
                $comunitarId = $comunitar->getId();
                // Verificăm dacă comunitarul nu a fost deja procesat și dacă calculata = true
                if (!in_array($comunitarId, $processedComunitare) && $comunitar->isCalculata()) {
                    $totalComunitare += $comunitar->getPret() ?? 0;
                    $processedComunitare[] = $comunitarId; // Marcăm comunitarul ca procesat
                }
            }
        }

        // Rotunjim sumele la 2 zecimale
        $totalComenzi = round($totalComenzi, 2);
        $totalComunitare = round($totalComunitare, 2);

        // Returnăm template-ul cu datele calculate
        return $this->render('statistici/cheltuieli.html.twig', [
            'an' => $an,
            'luna' => $luna,
            'total' => $total,
            'totalComenzi' => $totalComenzi,
            'totalComunitare' => $totalComunitare,
        ]);
    }
}