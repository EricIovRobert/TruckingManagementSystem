<?php

namespace App\Controller;

use App\Entity\Cheltuieli;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/rapoarte')]
class CheltuieliController extends AbstractController
{
    #[Route('/cheltuieli', name: 'app_cheltuieli_list', methods: ['GET'])]
    public function listCheltuieli(EntityManagerInterface $em): Response
    {
        $cheltuieli = $em->getRepository(Cheltuieli::class)->findAll();

        return $this->render('cheltuieli/index.html.twig', [
            'cheltuieli' => $cheltuieli,
        ]);
    }
}
