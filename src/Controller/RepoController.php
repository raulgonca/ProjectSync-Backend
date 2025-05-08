<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RepoController extends AbstractController
{
    #[Route('/repo', name: 'app_repo')]
    public function index(): Response
    {
        return $this->render('repo/index.html.twig', [
            'controller_name' => 'RepoController',
        ]);
    }
}
