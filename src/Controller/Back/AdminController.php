<?php

namespace App\Controller\Back;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin')]
#[IsGranted('ROLE_USER')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'back.admin', methods: ['GET', 'POST'])]
    public function admin(TranslatorInterface $translator): Response
    {
        return $this->render('pages/back/admin.html.twig',[
            'links' => [
                $translator->trans('Gestió de projectes')     => 'https://projectes.diba.cat',
                $translator->trans('Maqueta corporativa')     => 'https://maqueta.diba.cat',
                $translator->trans('Imatge corporativa')      => 'https://imatgecorporativa.diba.cat/',
                $translator->trans('Documentació de Symfony') => 'https://symfony.com/doc/6.4/index.html',
            ],
        ]);
    }
}
