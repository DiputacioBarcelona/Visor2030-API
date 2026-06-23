<?php

namespace App\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'login', methods: ['GET', 'POST'])]
    public function selector(): Response
    {
        if ($this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('back.admin');
        }

        return $this->render('pages/front/login/login-selector.html.twig');
    }

    #[Route('/login/in-vus', name: 'login.in_vus', methods: ['GET', 'POST'])]
    #[Route('/login/in-app', name: 'login.in_app', methods: ['GET', 'POST'])]
    #[Route('/login/in-memory', name: 'login.in_memory', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('back.admin');
        }

        $routeName = $this->container->get('request_stack')->getCurrentRequest()->attributes->get('_route');
        $label = $routeName === 'login.in_memory' ? 'Accés Usuaris de prova' : "Accés Usuaris Diputació de Barcelona";

        return $this->render('pages/front/login/login.html.twig', [
            'route'         => $routeName,
            'label'         => $label,
            'last_username' => $authenticationUtils->getLastUsername(),
            'error'         => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/login/in-diba', name: 'login.in_diba', methods: ['GET', 'POST'])]
    public function singleSignOn(): Response
    {
        if ($this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('back.admin');
        }

        return $this->redirectToRoute('login');
    }
}
