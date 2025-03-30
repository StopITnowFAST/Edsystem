<?php

namespace App\Controller;

use App\Service\Redirect;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class PagesController extends AbstractController {


    #[Route(path: '/{path}', name: 'catch_all', requirements: ['path' => '.+'], priority: -1)]
    function index(Request $request, $path = '') {        
        return new Response('Страница не найдена', 404);
    }
}