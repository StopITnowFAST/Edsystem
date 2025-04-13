<?php

namespace App\Controller;

use App\Entity\Answer;
use App\Entity\UserCard;
use App\Entity\User;
use App\Entity\Status;
use App\Entity\Student;
use App\Service\File;
use App\Entity\File as FileEntity;
use App\Entity\Group;
use App\Entity\Teacher;
use App\Service\TableWidget;
use App\Entity\Redirect;
use App\Entity\HeaderMenu;
use App\Entity\Question;
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

class TestController extends AbstractController {

    function __construct(
        private EntityManagerInterface $em, 
        private TableWidget $table,
        private File $file,
        private BreadcrumbsGenerator $breadcrumbs,
        private UrlGeneratorInterface $router,
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
        $question = $this->em->getRepository(Question::class)->find($questionId);
        $answers = $this->em->getRepository(Answer::class)->findBy(['question_id' => $questionId]);
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
        $notes = $this->em->getRepository(Question::class)->findBy(['test_id' => $testId]);
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
        $breadcrumbs = $this->breadcrumbs->registerBreadcrumbs([
            'Тесты' => 'admin_tests',
            'Добавить тест' => ['admin_update_note', ['id' => $testId, 'type' => 'tests']],
            'Редактировать вопросы' => ['admin_tests_redact', ['testId' => $testId]],
            'Добавить вопрос' => ['admin_tests_redact_add', ['testId' => $testId]],
        ], $this->router);
        
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
            $question = new Question();
            $question->setTestId($_POST['testId']);
            $question->setCorrectAnswers($answers['correct_count']);
            $question->setText($answers['question']);
            $this->em->persist($question);
            // $this->em->flush();

            $questionId = $question->getId();

            foreach ($answers['answers'] as $key => $answer) {
                $ans = new Answer();
                $ans->setCorrect($answer['is_correct']);
                $ans->setPoints($answer['points']);
                $ans->setText($answer['text']);
                $ans->setQuestionId($questionId);
                $this->em->persist($ans);
                // $this->em->flush();
            }
            
            $this->redirectToRoute('admin_tests_redact', ['testId' => $testId]);
        }
        
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
            $question = $this->em->getRepository(Question::class)->find($questionId);
            $question->setTestId($_POST['testId']);
            $question->setCorrectAnswers($answers['correct_count']);
            $question->setText($answers['question']);
            $this->em->persist($question);
            // $this->em->flush();

            $questionId = $question->getId();
            $oldAnswers = $this->em->getRepository(Answer::class)->findBy(['question_id' => $questionId]);
            foreach ($oldAnswers as $answer) {
                $this->em->remove($answer);
            }
            // $this->em->flush();

            foreach ($answers['answers'] as $key => $answer) {
                $ans = new Answer();
                $ans->setCorrect($answer['is_correct']);
                $ans->setPoints($answer['points']);
                $ans->setText($answer['text']);
                $ans->setQuestionId($questionId);
                $this->em->persist($ans);
                // $this->em->flush();
            }
            $this->redirectToRoute('admin_tests_redact', ['testId' => $testId]);
        }
        $breadcrumbs = $this->breadcrumbs->registerBreadcrumbs([
            'Тесты' => 'admin_tests',
            'Добавить тест' => ['admin_update_note', ['id' => $testId, 'type' => 'tests']],
            'Редактировать вопросы' => ['admin_tests_redact', ['testId' => $testId]],
            'Изменить вопрос' => ['admin_tests_redact_update', ['testId' => $testId, 'questionId' => $questionId]],
        ], $this->router);

        $question = $this->em->getRepository(Question::class)->find($questionId);
        $answers = $this->em->getRepository(Answer::class)->findBy(['question_id' => $questionId]);
        
        return $this->render('admin/tests/add_question.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'question' => $question,
            'answers' => $answers,
        ]);
    }

}
