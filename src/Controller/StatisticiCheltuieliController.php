<?php

namespace App\Controller;

use App\Entity\Cheltuieli;
use App\Entity\Comenzi;
use App\Entity\ComenziComunitare;
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

        // --- Cheltuieli ---
        $qbTotal = $em->getRepository(Cheltuieli::class)->createQueryBuilder('ch')
            ->select('SUM(
                CASE
                    WHEN ch.tva > 0 THEN
                        ch.suma - (ch.suma * ch.tva / (100 + ch.tva)) + 
                        ((ch.suma * ch.tva / (100 + ch.tva)) * (COALESCE(ch.comision_tva, 0) / 100)) +
                        (ch.suma * COALESCE(ch.comision_taxa_drum, 0) / 100)
                    ELSE
                        ch.suma + (ch.suma * COALESCE(ch.comision_taxa_drum, 0) / 100)
                END
            ) as total')
            ->leftJoin('ch.comanda', 'cmd')
            ->leftJoin('ch.comunitar', 'com')
            ->andWhere('
                (cmd.id IS NOT NULL AND cmd.calculata = :calculata_true) OR 
                (com.id IS NOT NULL AND com.calculata = :calculata_true) OR 
                (cmd.id IS NULL AND com.id IS NULL)
            ')
            ->setParameter('calculata_true', true);

        // --- Comenzi ---
        $qbComenzi = $em->getRepository(Comenzi::class)->createQueryBuilder('c')
            ->select('c')
            ->where('c.calculata = :calculata')
            ->setParameter('calculata', true);

        // --- Comunitare ---
        $qbComunitare = $em->getRepository(ComenziComunitare::class)->createQueryBuilder('cc')
            ->select('cc')
            ->where('cc.calculata = :calculata')
            ->setParameter('calculata', true);

        // Filtru an (dacă există)
        if ($anValid) {
            $startDate = new \DateTime("$an-01-01 00:00:00");
            $endDate = new \DateTime("$an-12-31 23:59:59");

            $qbTotal->andWhere('ch.data_cheltuiala >= :startDate')
                    ->andWhere('ch.data_cheltuiala <= :endDate')
                    ->setParameter('startDate', $startDate)
                    ->setParameter('endDate', $endDate);

            $qbComenzi->andWhere('c.dataStop >= :start')
                      ->andWhere('c.dataStop <= :end')
                      ->setParameter('start', $startDate)
                      ->setParameter('end', $endDate);

            $qbComunitare->andWhere('cc.data_stop >= :start')
                         ->andWhere('cc.data_stop <= :end')
                         ->setParameter('start', $startDate)
                         ->setParameter('end', $endDate);
        }

        // Filtru lună (dacă există)
        if ($lunaValid) {
            if ($anValid) {
                // An și lună selectate
                $startDate = new \DateTime("$an-$luna-01 00:00:00");
                $endDate = (clone $startDate)->modify('last day of this month')->setTime(23, 59, 59);

                $qbTotal->andWhere('ch.data_cheltuiala >= :s2')
                        ->andWhere('ch.data_cheltuiala <= :e2')
                        ->setParameter('s2', $startDate)
                        ->setParameter('e2', $endDate);

                $qbComenzi->andWhere('c.dataStop >= :s2')
                          ->andWhere('c.dataStop <= :e2')
                          ->setParameter('s2', $startDate)
                          ->setParameter('e2', $endDate);

                $qbComunitare->andWhere('cc.data_stop >= :s2')
                             ->andWhere('cc.data_stop <= :e2')
                             ->setParameter('s2', $startDate)
                             ->setParameter('e2', $endDate);
            } else {
                // Doar lună selectată (toți anii)
                $minMax = $em->createQueryBuilder()
                    ->select('MIN(ch.data_cheltuiala) as minDate, MAX(ch.data_cheltuiala) as maxDate')
                    ->from(Cheltuieli::class, 'ch')
                    ->getQuery()
                    ->getSingleResult();
                $minYear = $minMax['minDate'] ? (int) (new \DateTime($minMax['minDate']))->format('Y') : (int) date('Y');
                $maxYear = $minMax['maxDate'] ? (int) (new \DateTime($minMax['maxDate']))->format('Y') : (int) date('Y');

                $orXTotal = $qbTotal->expr()->orX();
                $orXComenzi = $qbComenzi->expr()->orX();
                $orXComunitare = $qbComunitare->expr()->orX();

                for ($year = $minYear; $year <= $maxYear; $year++) {
                    $start = new \DateTime("$year-$luna-01 00:00:00");
                    $end = (clone $start)->modify('last day of this month')->setTime(23, 59, 59);

                    $orXTotal->add($qbTotal->expr()->andX(
                        $qbTotal->expr()->gte('ch.data_cheltuiala', ":start_$year"),
                        $qbTotal->expr()->lte('ch.data_cheltuiala', ":end_$year")
                    ));
                    $qbTotal->setParameter("start_$year", $start)
                            ->setParameter("end_$year", $end);

                    $orXComenzi->add($qbComenzi->expr()->andX(
                        $qbComenzi->expr()->gte('c.dataStop', ":start_$year"),
                        $qbComenzi->expr()->lte('c.dataStop', ":end_$year")
                    ));
                    $qbComenzi->setParameter("start_$year", $start)
                              ->setParameter("end_$year", $end);

                    $orXComunitare->add($qbComunitare->expr()->andX(
                        $qbComunitare->expr()->gte('cc.data_stop', ":start_$year"),
                        $qbComunitare->expr()->lte('cc.data_stop', ":end_$year")
                    ));
                    $qbComunitare->setParameter("start_$year", $start)
                                 ->setParameter("end_$year", $end);
                }

                if ($orXTotal->count() > 0) {
                    $qbTotal->andWhere($orXTotal);
                }
                if ($orXComenzi->count() > 0) {
                    $qbComenzi->andWhere($orXComenzi);
                }
                if ($orXComunitare->count() > 0) {
                    $qbComunitare->andWhere($orXComunitare);
                }
            }
        }

        // Obținem totalul cheltuielilor
        $total = $qbTotal->getQuery()->getSingleScalarResult();
        $total = $total ? round($total, 2) : 0;

        // Calculăm sumele pentru comenzi și comunitare
        $totalComenzi = 0.0;
        $totalComunitare = 0.0;

        // Comenzi
        $comenzi = $qbComenzi->getQuery()->getResult();
        foreach ($comenzi as $comanda) {
            foreach ($comanda->getTururis() as $tur) {
                $totalComenzi += $tur->getPret() ?? 0;
            }
            foreach ($comanda->getRetururis() as $retur) {
                $totalComenzi += $retur->getPret() ?? 0;
            }
        }

        // Comunitare
        $comunitare = $qbComunitare->getQuery()->getResult();
        foreach ($comunitare as $comunitar) {
            $totalComunitare += $comunitar->getPret() ?? 0;
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