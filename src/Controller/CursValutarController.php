<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;

class CursValutarController extends AbstractController
{
    #[Route('/api/curs-valutar', name: 'app_curs_valutar', methods: ['GET'])]
    public function getCursValutar(): JsonResponse
    {
        // Creăm clientul HTTP
        $client = HttpClient::create();
        
        // Facem cererea către ExchangeRate-API cu cheia ta
        $response = $client->request('GET', 'https://v6.exchangerate-api.com/v6/46d356eb6cb5b3a1350fbfb7/latest/EUR');
        
        // Convertim răspunsul în array
        $data = $response->toArray();

        // Verificăm dacă cererea a fost reușită
        if ($data['result'] !== 'success') {
            return new JsonResponse(['error' => 'Eroare la preluarea cursului valutar'], 500);
        }

        // Construim răspunsul în formatul dorit
        $responseData = [
            'result' => $data['result'],
            'documentation' => $data['documentation'],
            'terms_of_use' => $data['terms_of_use'],
            'time_last_update_unix' => $data['time_last_update_unix'],
            'time_last_update_utc' => $data['time_last_update_utc'],
            'time_next_update_unix' => $data['time_next_update_unix'],
            'time_next_update_utc' => $data['time_next_update_utc'],
            'base_code' => $data['base_code'],
            'conversion_rates' => [
                'EUR' => 1,
                'RON' => $data['conversion_rates']['RON'],
            ],
        ];

        return new JsonResponse($responseData);
    }
}