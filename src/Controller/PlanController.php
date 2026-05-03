<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PlanController extends AbstractController
{
    #[Route('/', name: 'app_plan')]
    public function index(): Response
    {
        return $this->render('plan/index.html.twig');
    }
}
