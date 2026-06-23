<?php

namespace App\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;

class HomeController extends AbstractController
{
    #[Route('/', name: 'front.home', methods: ['GET'])]
    public function home(): RedirectResponse
    {
        return new RedirectResponse('/api');
    }

    // #[Route('/', name: 'front.home', methods: ['GET', 'POST'])]
    // public function home(): Response
    // {
    //     return $this->render('pages/front/home.html.twig', [
    //         'readme' => file_get_contents($this->getParameter('kernel.project_dir').'/README.md'),
    //     ]);
    // }

    // #[Route('/example', name: 'front.example', methods: ['GET', 'POST'])]
    // public function example(): Response
    // {
    //     return $this->render('pages/front/example.html.twig');
    // }
}
