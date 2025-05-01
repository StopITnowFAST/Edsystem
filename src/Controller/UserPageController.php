<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Service\Chat;
use App\Service\ApiTg;
use App\Service\UserService;
use App\Entity\Message;
use App\Entity\UserCard;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Psr\Log\LoggerInterface;

class UserPageController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em, 
        private LoggerInterface $logger,
    ) {        
    }

    // Отображение самой страницы
    #[Route(path: '/account', name:'user_account')]
    public function account() {
        return $this->render('user/main.html.twig', [

        ]);
    }
}
