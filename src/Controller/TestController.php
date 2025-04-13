<?php

namespace App\Controller;

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

    // // Добавление вопросов для теста
    // #[Route('/admin/tests', name: 'admin_tests_redact')]
    // function adminTestsRedact() {
    //     $breadcrumbs = $this->breadcrumbs->registerBreadcrumbs([
    //         'Тесты' => 'admin_tests',
    //         'Добавить тест' => ['admin_create_test', $testId],
    //     ], $this->router);


    //     $pagination = $this->table->createPagination($page, $this->em->getRepository(Group::class), self::PAGINATION_SIZE);
    //     return $this->render('admin/groups.html.twig', [
    //         'breadcrumbs' => $breadcrumbs,
    //         'notes' => $pagination['data'],
    //         'totalNotes' => $pagination['totalNotes'],
    //         'pagRow' => $pagination['row'],
    //         'currentPage' => $page,
    //         'paginationSize' => self::PAGINATION_SIZE,
    //         'formName' => 'admin_groups',
    //     ]);
    // }
}
