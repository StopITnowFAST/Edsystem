<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Entity\User;
use App\Entity\UserCard;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityController extends AbstractController
{
    private $em;
    public function __construct(
        EntityManagerInterface $em, 
    ) {
        $this->em = $em;
    }    

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        $successReg = $request->query->get('successReg');
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
            'successReg' => $successReg,
        ]);
    }

    #[Route('/register', name: 'app_register')]
    public function register(UserPasswordHasherInterface $passwordHasher, Request $request): Response
    {
        if($request->isMethod('POST')) {
            if($this->isAccountExists($request->request->get('login'))) {
                $error['code'] = 422;
                $error['message'] = "Аккаунт с таким Email уже существует";
                $response = new Response(null, $error['code']);
                return $this->render('security/register.html.twig', [
                    'error' => $error,
                ], $response);
            }            

            $this->addUser($passwordHasher, $request);
            
            return $this->redirectToRoute('app_login', [
                'successReg' => true,
            ]);
        }
        return $this->render('security/register.html.twig', [
            'error' => null,
        ]);
    }

    function isAccountExists($login) {
        $account = $this->em->getRepository(User::class)->findOneBy(['email' => $login]);
        return isset($account);
    }

    function addUser(UserPasswordHasherInterface $passwordHasher, Request $request) {
        $email = $request->request->get('login');
        if (preg_match('/([A-z0-9_.-]{1,})@([A-z0-9_.-]{1,}).([A-z]{2,8})/', $email)) {
            $user = new User();
            $hashedPassword = $passwordHasher->hashPassword($user,$request->request->get('password'));
            $roles[] = 'ROLE_USER';           
            $user->setEmail($request->request->get('login'));
            $user->setPassword($hashedPassword);
            $user->setRoles($roles);
            $this->em->persist($user);
            $this->em->flush();
        }
        else {
            return new Response('Email error', 400);
        }
    }
    
    #[Route('/after-login/redirect', name: 'app_check_rights')]
    public function routeToCheckRights() {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Проверяем роли и перенаправляем
        if ($this->isGranted('ROLE_MASTER')) {
            return $this->redirectToRoute('admin_moderators');
        }
        
        if ($this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('user_schedule');
        }
    }
}
