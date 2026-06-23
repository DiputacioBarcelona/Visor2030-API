<?php

namespace App\Controller;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;

class AuthController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(Security $security, JWTTokenManagerInterface $JWTManager)
    {
        // Get the currently authenticated user
        $user = $security->getUser(); // This will return the authenticated user after the json_login firewall runs

        if (!$user) {
            return new JsonResponse(['error' => 'Invalid credentials'], 401);
        }

        // Generate JWT token using the authenticated user
        $token = $JWTManager->create($user);

        // Return the JWT token as part of the response
        return new JsonResponse(['token' => $token]);
    }
}
