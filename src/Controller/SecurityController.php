<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Entity\User;
use App\Entity\VkUser;
use App\Entity\UserCard;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use Symfony\Component\Security\Core\Security;
    
// use Symfony\Component\Security\Core\User\UserInterface;

class SecurityController extends AbstractController
{
    
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
    ) {}

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
        } else {
            return $this->redirectToRoute('app_login');
        }
    }
    
    
    #[Route('/login/vk', name: 'app_login_vk')]
    public function loginVk(UserPasswordHasherInterface $passwordHasher, Request $request) {
        if($request->isMethod('POST')) {
            $vk_id = $_POST['vk_id'];
            $vkUser = $this->em->getRepository(VkUser::class)->findOneBy(['vk_id' => $vk_id]);
            if ($vkUser) {
                // Обновляю данные пользователя ВК
                $vkUser = $this->setVkData($vkUser, $_POST);
                $user = $this->em->getRepository(User::class)->find($vkUser->getUserId());
            } else {
                // Создаю новый профиль пользователя
                $vkUser = new VkUser;
                $user = new User();
                $hashedPassword = $passwordHasher->hashPassword($user, $this->generateRandomString());
                $roles[] = 'ROLE_USER';           
                $user->setEmail($_POST['vk_id']);
                $user->setPassword($hashedPassword);
                $user->setRoles($roles);
                $this->em->persist($user);
                $this->em->flush();
                $vkUser = $this->setVkData($vkUser, $_POST, $user->getId());
            }
            $this->security->login($user);
            return $this->redirectToRoute('app_check_rights');
        }
        return $this->redirectToRoute('app_check_rights');
    }

    function setVkData($vkUser, $post, $user_id = null) {
        $vkUser->setVkId($_POST['vk_id']);
        $vkUser->setFirstName($_POST['first_name']);
        $vkUser->setLastName($_POST['last_name']);
        $vkUser->setAvatar($_POST['avatar']);
        $vkUser->setBirthday($_POST['birthday']);
        $vkUser->setGender($_POST['gender']);
        $vkUser->setVerified($_POST['verified']);
        if ($user_id != null) {
            $vkUser->setUserId($user_id);
        }
        $this->em->persist($vkUser);
        $this->em->flush();
        return $vkUser;
    }

    function generateRandomString(int $length = 10): string {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $result = '';        
        for ($i = 0; $i < $length; $i++) {
            $result .= $characters[random_int(0, strlen($characters) - 1)];
        }        
        return $result;
    }
}
