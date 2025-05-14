<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    #[Route('/api/main', name: 'api_main', methods: ['GET'])]
    
    public function apiWelcome(): JsonResponse
    {
        $data = [
            'mensaje' => 'Bienvenido a la API del proyecto',
            'proyecto' => 'ProjectSync',
            'descripcion' => 'TFG del módulo de Desarrollo de Aplicaciones Web (DAW)',
            'autor' => 'Raúl González',
            'fecha' => '2025',
        ];

        return new JsonResponse($data);
    }
}
