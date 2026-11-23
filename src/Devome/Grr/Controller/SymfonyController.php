<?php

namespace Devome\Grr\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SymfonyController extends AbstractController
{
    #[Route('/_symfony', name: 'symfony_home', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('symfony/home.html.twig');
    }
}
