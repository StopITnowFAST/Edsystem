<?php

namespace App\Controller;

use App\Entity\TestAnswer;
use App\Entity\UserCard;
use App\Entity\User;
use App\Entity\Status;
use App\Entity\Student;
use App\Service\File;
use App\Entity\File as FileEntity;
use App\Entity\Group;
use App\Entity\Teacher;
use App\Service\TableWidget;
use App\Service\Help;
use App\Entity\Shuffle;
use App\Entity\TestParams;
use App\Entity\TestQuestion;
use App\Entity\Test;
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
use Symfony\Bundle\SecurityBundle\Security;
use App\Service\BreadcrumbsGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use App\Service\Study;

class TestController extends AbstractController {

    function __construct(
        private EntityManagerInterface $em, 
        private TableWidget $table,
        private File $file,
        private BreadcrumbsGenerator $breadcrumbs,
        private UrlGeneratorInterface $router,
        private Study $study,
    ) {
    }  

    // Назначение групп для теста
    #[Route('/admin/tests/{testId}/appoint', name: 'admin_tests_appoint')]
    function adminTestsAppoint($testId, Request $request) {
        if ($request->isMethod('POST')) {
            $currentTest = $this->em->getRepository(Test::class)->find($testId);
            $currentGroups = $currentTest->getStudentGroups();
            if (!str_contains($currentGroups, $_POST['group'])) {
                $currentGroups .= ' ' . $_POST['group'] . ',';
                $currentTest->setStudentGroups($currentGroups);
                $this->em->persist($currentTest);
                $this->em->flush();
            }
        }
        $breadcrumbs = $this->breadcrumbs->registerBreadcrumbs([
            'Тесты' => 'admin_tests',
            'Добавить тест' => ['admin_update_note', ['id' => $testId, 'type' => 'tests']],
            'Назначить тест' => ['admin_tests_appoint', ['testId' => $testId]]
        ], $this->router);
        $groups = $this->em->getRepository(Group::class)->findAll();
        $notes = $this->em->getRepository(Group::class)->findTestGroups($testId);
        return $this->render('admin/tests/appoint.html.twig', [
            'groups' => $groups,
            'notes' => $notes,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    // Удаление группы из теста
    #[Route('/admin/tests/{testId}/appoint/delete/{groupId}', name: 'admin_tests_appoint_delete')]
    function adminTestsAppointDelete($testId, $groupId) {
        $test = $this->em->getRepository(Test::class)->find($testId);
        $groupsForTest = $test->getStudentGroups();
        $newGroupsString = str_replace(" $groupId,", '', $groupsForTest);
        $test->setStudentGroups($newGroupsString);
        $this->em->persist($test);
        $this->em->flush();
        return $this->redirectToRoute('admin_tests_appoint', ['testId' => $testId]);
    }

    // Удаление вопроса
    #[Route('/admin/tests/{testId}/redact/delete/{questionId}', name: 'admin_tests_redact_delete')]
    function adminTestsRedactDelete($testId, $questionId) {
        $question = $this->em->getRepository(TestQuestion::class)->find($questionId);
        $answers = $this->em->getRepository(TestAnswer::class)->findBy(['question_id' => $questionId]);
        foreach ($answers as $answer) {
            $this->em->remove($answer);
        }
        $this->em->remove($question);
        $this->em->flush();
        return $this->redirectToRoute('admin_tests_redact', ['testId' => $testId]);
    }

    // Редактирование вопросов для теста
    #[Route('/admin/tests/{testId}/redact', name: 'admin_tests_redact')]
    function adminTestsRedact($testId, Request $request) {
        $breadcrumbs = $this->breadcrumbs->registerBreadcrumbs([
            'Тесты' => 'admin_tests',
            'Добавить тест' => ['admin_update_note', ['id' => $testId, 'type' => 'tests']],
            'Редактировать вопросы' => ['admin_tests_redact', ['testId' => $testId]]
        ], $this->router);        
        $notes = $this->em->getRepository(TestQuestion::class)->findBy(['test_id' => $testId]);
        return $this->render('admin/tests/redact.html.twig', [
            'notes' => $notes,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }


    // Добавление вопросов для теста
    #[Route('/admin/tests/{testId}/redact/add', name: 'admin_tests_redact_add')]
    function adminTestsRedactAdd(
        $testId, 
        Request $request
    ) {
        if ($request->isMethod('POST')) {
            // Обработка данных
            $answers = [];
            $answers['correct_count'] = 0;
            $answers['question'] = $_POST['question_text'];
            foreach ($_POST as $key => $value) {
                if (str_starts_with($key, 'answers_')) {
                    $id = substr($key, strlen('answers_'));
                    $answers['answers'][$id] = [
                        'text' => $value,
                        'points' => (int) $_POST['points_'.$id] ?? '',
                        'is_correct' => isset($_POST['isCorrect_'.$id])
                    ];
                    $answers['correct_count'] += ($answers['answers'][$id]['is_correct']) ? 1 : 0;
                }
            }
            // Сохранение данных
            $question = new TestQuestion();
            $question->setTestId($_POST['testId']);
            $question->setCorrectAnswers($answers['correct_count']);
            $question->setText($answers['question']);
            $this->em->persist($question);
            $this->em->flush();

            $questionId = $question->getId();

            foreach ($answers['answers'] as $key => $answer) {
                $ans = new TestAnswer();
                $ans->setCorrect($answer['is_correct']);
                $ans->setPoints($answer['points']);
                $ans->setText($answer['text']);
                $ans->setQuestionId($questionId);
                $this->em->persist($ans);
                $this->em->flush();
            }            
            return $this->redirectToRoute('admin_tests_redact', ['testId' => $testId]);
        }
        $breadcrumbs = $this->breadcrumbs->registerBreadcrumbs([
            'Тесты' => 'admin_tests',
            'Добавить тест' => ['admin_update_note', ['id' => $testId, 'type' => 'tests']],
            'Редактировать вопросы' => ['admin_tests_redact', ['testId' => $testId]],
            'Добавить вопрос' => ['admin_tests_redact_add', ['testId' => $testId]],
        ], $this->router);       
        
        return $this->render('admin/tests/add_question.html.twig', [
            'breadcrumbs' => $breadcrumbs,
        ]);
    }


    // Редактирование вопросов для теста
    #[Route('/admin/tests/{testId}/redact/update/{questionId}', name: 'admin_tests_redact_update')]
    function adminTestsRedactUpdate(
        $testId, 
        $questionId,
        Request $request
    ) { 
        if ($request->isMethod('POST')) {
            // Обработка данных
            $answers = [];
            $answers['correct_count'] = 0;
            $answers['question'] = $_POST['question_text'];
            foreach ($_POST as $key => $value) {
                if (str_starts_with($key, 'answers_')) {
                    $id = substr($key, strlen('answers_'));
                    $answers['answers'][$id] = [
                        'text' => $value,
                        'points' => (int) $_POST['points_'.$id] ?? '',
                        'is_correct' => isset($_POST['isCorrect_'.$id])
                    ];
                    $answers['correct_count'] += ($answers['answers'][$id]['is_correct']) ? 1 : 0;
                }
            }
            // Сохранение данных
            $question = $this->em->getRepository(TestQuestion::class)->find($questionId);
            $question->setTestId($_POST['testId']);
            $question->setCorrectAnswers($answers['correct_count']);
            $question->setText($answers['question']);
            $this->em->persist($question);
            $this->em->flush();

            $questionId = $question->getId();
            $oldAnswers = $this->em->getRepository(TestAnswer::class)->findBy(['question_id' => $questionId]);
            foreach ($oldAnswers as $answer) {
                $this->em->remove($answer);
            }
            $this->em->flush();

            foreach ($answers['answers'] as $key => $answer) {
                $ans = new TestAnswer();
                $ans->setCorrect($answer['is_correct']);
                $ans->setPoints($answer['points']);
                $ans->setText($answer['text']);
                $ans->setQuestionId($questionId);
                $this->em->persist($ans);
                $this->em->flush();
            }
            return $this->redirectToRoute('admin_tests_redact', ['testId' => $testId]);
        }
        $breadcrumbs = $this->breadcrumbs->registerBreadcrumbs([
            'Тесты' => 'admin_tests',
            'Добавить тест' => ['admin_update_note', ['id' => $testId, 'type' => 'tests']],
            'Редактировать вопросы' => ['admin_tests_redact', ['testId' => $testId]],
            'Изменить вопрос' => ['admin_tests_redact_update', ['testId' => $testId, 'questionId' => $questionId]],
        ], $this->router);

        $question = $this->em->getRepository(TestQuestion::class)->find($questionId);
        $answers = $this->em->getRepository(TestAnswer::class)->findBy(['question_id' => $questionId]);
        
        return $this->render('admin/tests/add_question.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'question' => $question,
            'answers' => $answers,
        ]);
    }
    
    // Превью теста
    #[Route('/tests/preview/{testId}', name: 'user_test_preview')]
    function testPreview($testId) {
        $test = $this->em->getRepository(Test::class)->find($testId);
        if (!$test) {
            return new Response('Тест не найден', 404);
        } 
        $userId = $this->getUser()->getId();
        $testData = $this->em->getRepository(Test::class)->getTestData($testId);        
        $totalQuestions = $this->em->getRepository(Test::class)->getQuestinsCount($testId);
        $totalPoints = $this->em->getRepository(Test::class)->countTotalPoints($testId);
        $attemptsLeft = $this->study->getAttemptsForTest($userId, $testId);
        
        return $this->render('user/test_preview.html.twig', [
            'testData' => $testData,
            'totalQuestions' => $totalQuestions,
            'totalPoints' => $totalPoints,
            'attemptsLeft' => $attemptsLeft,
            'test' => $test,
        ]);        
    }
    
    // Начать тест
    #[Route('/tests/start/{testId}', name: 'user_test_main')]
    function testMain($testId, Help $help, Security $security) {
        $test = $this->em->getRepository(Test::class)->find($testId);
        if (!$test) {
            return new Response('Тест не найден', 404);
        }

        $seed = $help->generateRandomString();
        $shuffle = new TestParams();
        $shuffle->setUserId($security->getUser()->getId());
        $shuffle->setShuffleSeed($seed);
        $shuffle->setTimeStart(time());
        $parts = explode(':', $test->getTime());
        $shuffle->setTimeEnd(($parts[0] * 3600) + ($parts[1] * 60) + time());
        $this->em->persist($shuffle); $this->em->flush();
        
        return $this->redirectToRoute('user_test_pass', [
            'testId' => $testId,
            'questionPosition' => 0,
        ]);
    }

    #[Route('/tests/{testId}/question/{questionPosition}', name: 'user_test_pass')]
    function question($testId, $questionPosition, Security $security) {
        $testRep = $this->em->getRepository(Test::class);
        $test = $testRep->find($testId);
        $shuffle = $this->em->getRepository(TestParams::class)->findOneBy(['user_id' => $security->getUser()->getId()]);
        $questions = $testRep->getQuestionIds($testId);
        $totalQuestions = $testRep->getQuestinsCount($testId);
        $neededQuestion = $this->deterministicShuffle($questions, $shuffle->getShuffleSeed())[$questionPosition];
        $question = $this->em->getRepository(TestQuestion::class)->find($neededQuestion);
        $answers = $this->em->getRepository(TestAnswer::class)->findBy(['question_id' => $neededQuestion]);
        $maxQuestions = $test->getMaxQuestions ?? $totalQuestions;

        return $this->render('user/test_main.html.twig', [
            'testTitle' => $test->getName(),
            'maxQuestions' => $maxQuestions,
            'testId' => $testId,
            'question' => $question,
            'answers' => $answers,
            'questionPosition' => $questionPosition,
            'totalQuestions' => $totalQuestions,
            'timeStart' => $shuffle->getTimeStart(),
            'timeEnd' => $shuffle->getTimeEnd(),
        ]);  
    }   

    #[Route('/tests/{testId}/answer/{answeringQuestion}', name: 'user_test_answer')]
    function answer($testId, $questionPosition, Security $security) {
        return $this->redirectToRoute('user_test_pass');
    }   









    

    function deterministicShuffle(array $array, string|int $key): array {
        $seed = crc32($key); 
        mt_srand($seed);
        usort($array, function() {
            return mt_rand() - mt_rand(); 
        });        
        return $array;
    }
}
