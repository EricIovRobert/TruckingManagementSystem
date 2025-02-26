<?php

namespace App\Controller;

use App\Repository\ParcAutoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/parc-auto')]
class ParcAutoController extends AbstractController
{
    #[Route('/', name: 'app_parc_auto_index')]
    public function index(ParcAutoRepository $parcAutoRepository): Response
    {
        // Preia toate înregistrările din tabelul "parc_auto"
        $parcAuto = $parcAutoRepository->findAll();

        return $this->render('parc_auto/index.html.twig', [
            'parc_auto' => $parcAuto,
        ]);
    }
}
