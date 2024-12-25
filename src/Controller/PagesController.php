<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\Slugger\SluggerInterface;

class PagesController extends AbstractController {

    private $em;
    function __construct(
        EntityManagerInterface $em,
    ) {
        $this->em = $em;     
    }

    // Учительская страница редактирования ученика
    #[Route(path: '/panel', name: 'app_panel')] 
    function panelPage(Request $request, SluggerInterface $slugger) {
        return $this->render('simplePages/panel.html.twig');        
    }

    // Главная
    #[Route(path: '/', name: 'app_main')] 
    function mainPage(Request $request, SluggerInterface $slugger) {
        return $this->render('simplePages/main.html.twig');        
    }
}