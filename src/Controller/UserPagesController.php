<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Form\RegistrationFormType;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Request;

class UserPagesController extends AbstractController {

    // Домашняя страница
    #[Route('/', name: 'homepage')]
    public function homepage() {
        return new Response('Success');
    }

    // Страница с расписанием
    #[Route('/schedule', name: 'user_schedule')]
    public function schedule() {
        return new Response('Success');
    }
}