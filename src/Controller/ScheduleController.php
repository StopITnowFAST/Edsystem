<?php

namespace App\Controller;

use App\Entity\Student;
use App\Entity\Schedule;
use App\Entity\Day;
use App\Entity\Lesson;
use App\Entity\Group;
use App\Entity\Subject;
use DateTime;
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

class ScheduleController extends AbstractController {

    private $em;
    function __construct(
        EntityManagerInterface $em,
    ) {
        $this->em = $em;     
    }

    #[Route('/panel/schedule/edit/{id}', name:'edit_schedule', methods: ['POST'])]
    function editSchedule($id) {
        // Получить расписание группы
        $schedule = $this->em->getRepository(Schedule::class)->findOneBy(['group_id' => $id]);
        if($schedule) {
            $days = $this->em->getRepository(Day::class)->findBy(['schedule_id' => $schedule->getId()]);
        
            // Сопоставляем дни недели с уроками
            $lessonsData = [];
            foreach ($days as $day) {
                $dayNumber = $day->getDayNumber();
                $lessonsData[$dayNumber] = $this->em->getRepository(Lesson::class)->findBy(['day_id' => $day->getId()]);
            }
            foreach ($this->em->getRepository(Subject::class)->findAll() as $subject) {
                $subjects[] = [
                    'name' => $subject->getName(),
                    'id' => $subject->getId(),
                ];
            }
        }

        return $this->render('update/schedule.html.twig', [
            'group_id' => $id,
            'lessonsData' => $lessonsData ?? null,
            'lessons' => $subjects ?? null,
        ]);
    }

    #[Route('/panel/schedule/save/{group_id}', name: 'save_schedule', methods: ['POST'])]
    public function saveSchedule(Request $request, int $group_id, EntityManagerInterface $entityManager): Response
    {
        $lessonsData = $request->request->all();

        $deletedLessons = json_decode($request->request->get('deleted_lessons', '[]'), true);

        // Удаление существующего расписания для группы
        $scheduleRepository = $entityManager->getRepository(Schedule::class);
        $dayRepository = $entityManager->getRepository(Day::class);
        $lessonRepository = $entityManager->getRepository(Lesson::class);

        // Найти старое расписание группы
        $existingSchedule = $scheduleRepository->findOneBy(['group_id' => $group_id]);
        if ($existingSchedule) {
            // Удалить дни и уроки, связанные с расписанием
            $days = $dayRepository->findBy(['schedule_id' => $existingSchedule->getId()]);
            foreach ($days as $day) {
                $lessons = $lessonRepository->findBy(['day_id' => $day->getId()]);
                foreach ($lessons as $lesson) {
                    $entityManager->remove($lesson);
                }
                $entityManager->remove($day);
            }
            $entityManager->remove($existingSchedule);
        }

        // Удаление уроков по ID, переданных через deleted_lessons
        if (!empty($deletedLessons)) {
            foreach ($deletedLessons as $lessonId) {
                $lesson = $lessonRepository->find($lessonId);
                if ($lesson) {
                    $entityManager->remove($lesson);
                }
            }
        }

        // Сохранение нового расписания
        $schedule = new Schedule();
        $schedule->setGroupId($group_id);
        $schedule->setStatus(1);
        $entityManager->persist($schedule);
        $entityManager->flush();

        if(array_key_exists('lessons', $lessonsData)) {
            foreach ($lessonsData['lessons'] as $dayNumber => $lessons) {
                $day = new Day();
                $day->setDayNumber($dayNumber);
                $day->setScheduleId($schedule->getId());
                $entityManager->persist($day);
                $entityManager->flush();
                
                $counter = 0;
                foreach ($lessons as $lessonName) {
                    if(isset($lessons['id'][$counter])) {
                        // print_r($lessons['id']); die;
                        $lessonEntity = new Lesson();
                        $lessonEntity->setSubjectId($lessons['id'][$counter]);
                        $lessonEntity->setTeacher($lessons['teacher'][$counter]);
                        $lessonEntity->setCabinet($lessons['cabinet'][$counter]);
                        $lessonEntity->setStartTime($lessons['start_time'][$counter]);
                        $lessonEntity->setEndTime($lessons['end_time'][$counter]);
                        $lessonEntity->setDayId($day->getId());
                        $entityManager->persist($lessonEntity);
                        $counter++;
                    }
                }
            }
        
            $entityManager->flush();
            // print_r('Можно вставлять');
            // die;
        }    

        return $this->redirectToRoute('panel_groups');
    }


}