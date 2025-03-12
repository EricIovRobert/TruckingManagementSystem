<?php

namespace App\Controller;

use App\Entity\Cheltuieli;
use App\Entity\Comenzi;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StatisticiCheltuieliController extends AbstractController
{
    #[Route('/statistici/cheltuieli', name: 'app_statistici_cheltuieli')]
    public function statisticiCheltuieli(Request $request, EntityManagerInterface $em): Response
    {
        // Preia parametrii din query (an și lună)
        $an = $request->query->get('an');
        $luna = $request->query->get('luna');

        // Validare an (trebuie să fie un număr de 4 cifre)
        $anValid = false;
        if ($an && preg_match('/^\d{4}$/', $an)) {
            $anValid = true;
        }

        // Validare lună (între 1 și 12)
        $lunaValid = false;
        if ($luna && in_array($luna, range(1, 12))) {
            $lunaValid = true;
            $luna = str_pad($luna,1);
        }

        // Construim QueryBuilder pentru profitul comenzilor
        $qbProfit = $em->getRepository(Comenzi::class)->createQueryBuilder('c')
            ->select('SUM(c.profit) as totalProfit');

        // Filtru an pentru profitul comenzilor
        if ($anValid) {
            $startDate = new \DateTime("$an-01-01 00:00:00");
            $endDate = new \DateTime("$an-12-31 23:59:59");
            $qbProfit->andWhere('c.dataStart >= :startDate')
                     ->andWhere('c.dataStart <= :endDate')
                     ->setParameter('startDate', $startDate)
                     ->setParameter('endDate', $endDate);
        }

        // Filtru lună pentru profitul comenzilor
        if ($lunaValid) {
            if ($anValid) {
                $startDate = new \DateTime("$an-$luna-01 00:00:00");
                $endDate = (clone $startDate)->modify('last day of this month')->setTime(23, 59, 59);
                $qbProfit->andWhere('c.dataStart >= :s2')
                         ->andWhere('c.dataStart <= :e2')
                         ->setParameter('s2', $startDate)
                         ->setParameter('e2', $endDate);
            } else {
                // Dacă nu este specificat anul, filtrăm pentru luna respectivă în toți anii
                $minMax = $em->createQueryBuilder()
                    ->select('MIN(c.dataStart) as minDate, MAX(c.dataStart) as maxDate')
                    ->from(Comenzi::class, 'c')
                    ->getQuery()
                    ->getSingleResult();
                $minDate = $minMax['minDate'] ? new \DateTime($minMax['minDate']) : new \DateTime();
                $maxDate = $minMax['maxDate'] ? new \DateTime($minMax['maxDate']) : new \DateTime();
                $minYear = (int) $minDate->format('Y');
                $maxYear = (int) $maxDate->format('Y');
                $orX = $qbProfit->expr()->orX();
                for ($year = $minYear; $year <= $maxYear; $year++) {
                    $start = new \DateTime("$year-$luna-01 00:00:00");
                    $end = (clone $start)->modify('last day of this month')->setTime(23, 59, 59);
                    $orX->add($qbProfit->expr()->andX(
                        $qbProfit->expr()->gte('c.dataStart', ":start_$year"),
                        $qbProfit->expr()->lte('c.dataStart', ":end_$year")
                    ));
                    $qbProfit->setParameter("start_$year", $start)
                             ->setParameter("end_$year", $end);
                }
                if ($orX->count() > 0) {
                    $qbProfit->andWhere($orX);
                }
            }
        }

        // Obținem totalul profitului comenzilor
        $totalProfitComenzi = $qbProfit->getQuery()->getSingleScalarResult() ?? 0;

        // Construim QueryBuilder pentru cheltuielile nelegate
        $qbCheltuieli = $em->getRepository(Cheltuieli::class)->createQueryBuilder('ch')
            ->select('SUM(
                CASE
                    WHEN ch.tva > 0 THEN
                        ch.suma - (ch.suma * ch.tva / (100 + ch.tva)) + 
                        ((ch.suma * ch.tva / (100 + ch.tva)) * (COALESCE(ch.comision_tva, 0) / 100))
                    ELSE
                        ch.suma
                END
            ) as totalCheltuieli')
            ->andWhere('ch.comanda IS NULL');

        // Filtru an pentru cheltuielile nelegate
        if ($anValid) {
            $startDate = new \DateTime("$an-01-01 00:00:00");
            $endDate = new \DateTime("$an-12-31 23:59:59");
            $qbCheltuieli->andWhere('ch.data_cheltuiala >= :startDate')
                         ->andWhere('ch.data_cheltuiala <= :endDate')
                         ->setParameter('startDate', $startDate)
                         ->setParameter('endDate', $endDate);
        }

        // Filtru lună pentru cheltuielile nelegate
        if ($lunaValid) {
            if ($anValid) {
                $startDate = new \DateTime("$an-$luna-01 00:00:00");
                $endDate = (clone $startDate)->modify('last day of this month')->setTime(23, 59, 59);
                $qbCheltuieli->andWhere('ch.data_cheltuiala >= :s2')
                             ->andWhere('ch.data_cheltuiala <= :e2')
                             ->setParameter('s2', $startDate)
                             ->setParameter('e2', $endDate);
            } else {
                // Dacă nu este specificat anul, filtrăm pentru luna respectivă în toți anii
                $minMax = $em->createQueryBuilder()
                    ->select('MIN(ch.data_cheltuiala) as minDate, MAX(ch.data_cheltuiala) as maxDate')
                    ->from(Cheltuieli::class, 'ch')
                    ->getQuery()
                    ->getSingleResult();
                $minDate = $minMax['minDate'] ? new \DateTime($minMax['minDate']) : new \DateTime();
                $maxDate = $minMax['maxDate'] ? new \DateTime($minMax['maxDate']) : new \DateTime();
                $minYear = (int) $minDate->format('Y');
                $maxYear = (int) $maxDate->format('Y');
                $orX = $qbCheltuieli->expr()->orX();
                for ($year = $minYear; $year <= $maxYear; $year++) {
                    $start = new \DateTime("$year-$luna-01 00:00:00");
                    $end = (clone $start)->modify('last day of this month')->setTime(23, 59, 59);
                    $orX->add($qbCheltuieli->expr()->andX(
                        $qbCheltuieli->expr()->gte('ch.data_cheltuiala', ":start_$year"),
                        $qbCheltuieli->expr()->lte('ch.data_cheltuiala', ":end_$year")
                    ));
                    $qbCheltuieli->setParameter("start_$year", $start)
                                 ->setParameter("end_$year", $end);
                }
                if ($orX->count() > 0) {
                    $qbCheltuieli->andWhere($orX);
                }
            }
        }

        // Obținem totalul cheltuielilor nelegate
        $totalCheltuieliNelgate = $qbCheltuieli->getQuery()->getSingleScalarResult() ?? 0;

        // Calculăm profitul total
        $profitTotal = $totalProfitComenzi - $totalCheltuieliNelgate;

        // Lista cu numele lunilor pentru afișare
        $lunaNume = [
            '1' => 'Ianuarie', '2' => 'Februarie', '3' => 'Martie', '4' => 'Aprilie',
            '5' => 'Mai', '6' => 'Iunie', '7' => 'Iulie', '8' => 'August',
            '9' => 'Septembrie', '10' => 'Octombrie', '11' => 'Noiembrie', '12' => 'Decembrie',
        ];

        // Returnăm template-ul cu datele calculate
        return $this->render('statistici/cheltuieli.html.twig', [
            'an' => $an,
            'luna' => $luna,
            'profitTotal' => $profitTotal,
            'lunaNume' => $lunaNume,
        ]);
    }
}