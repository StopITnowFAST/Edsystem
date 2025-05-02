<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Service\Study;
use App\Service\ApiTg;
use App\Service\UserService;
use App\Entity\Test;
use App\Entity\TestUserResult;
use App\Entity\UserCard;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Psr\Log\LoggerInterface;

class UserPageController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em, 
        private LoggerInterface $logger,
        private Study $study,
    ) {
    }
    
    // Домашняя страница
    #[Route('/', name: 'homepage')]
    public function homepage() {
        return $this->redirectToRoute('account');
    }

    // Отображение самой страницы
    #[Route(path: '/account/{section}', name:'account')]
    public function account($section = 'schedule') {
        return $this->render('user/main.html.twig', [
            'userId' => $this->getUser()->getId(),
            'section' => $section,
        ]);
    }

    // Получение данных для теста
    #[Route(path: '/request/get/user/tests/{userId}', name:'get_tests')]
    public function getTests($userId) {
        if ($this->getUser()->getId() != $userId) {
            return new Response('Permission Error', 403);
        }
        $tests = $this->study->getTestsForStudent($userId);
        $formattedTests = [];
        foreach ($tests as $key => $test) {
            $bestGrade = $this->em->getRepository(TestUserResult::class)->getBestGrade($userId, $test['id']);
            $formattedTests[$key]['id'] = $test['id'];
            $formattedTests[$key]['title'] = $test['name'];
            $formattedTests[$key]['status'] = "Не разработан";
            $formattedTests[$key]['grade'] = $bestGrade[0]['grade'] ?? '-';
            $formattedTests[$key]['attemptsLeft'] = $this->study->getAttemptsForTest($userId, $test['id']);
            $formattedTests[$key]['questionsCount'] = $this->em->getRepository(Test::class)->getQuestinsCount($test['id']);
            $formattedTests[$key]['timeLimit'] = $test['time'];
        }

        return $this->json([
            'data' => $formattedTests,
        ]);
    }
    
    // Для тестов
    #[Route('/testss', name: 'test')]
    public function test() {
        var_dump($this->study->getAttemptsForTest(2, 3)); die;
    }    
}
