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
use App\Entity\Schedule;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Security;
use Psr\Log\LoggerInterface;

class AjaxController extends AbstractController
{    
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
    ) {}    

    // Смена ролей для страницы модераторов
    #[Route(path: '/request/change/role', name: 'request_change_role')] 
    function changeRole() {    
        $postJsonArray = json_decode(file_get_contents("php://input"), true);
        $user = $this->em->getRepository(User::class)->find($postJsonArray['id']);
        $roles = $user->getRoles();
        $key = array_search("ROLE_MODERATOR", $roles);        
        if ($key !== false) {
            unset($roles[$key]);
            $roles = array_values($roles);
        } 
        else {
            array_push($roles, "ROLE_MODERATOR");
        }
        $user->setRoles($roles);
        $this->em->persist($user);
        $this->em->flush();
        return $this->json([
            'ok' => true,
        ]);
    }

    // Сохранение расписания для группы 
    #[Route(path: '/request/save/schedule/{groupId}', name: 'save_schedule')] 
    function saveSchedule($groupId) {
        $json = json_decode(file_get_contents("php://input"), true);

        // Находим все прошлые записи расписаний для группы
        $oldSchedule = $this->em->getRepository(Schedule::class)->findBy(['schedule_group_id' => $groupId]);

        foreach ($json['schedule'] as $scheduleNote) {
            $newSchedule = new Schedule();
            $newSchedule->setWeekNumber($scheduleNote['week_number']);
            $newSchedule->setScheduleDay($scheduleNote['schedule_day']);
            $newSchedule->setScheduleTimeId($scheduleNote['schedule_time_id']);
            $newSchedule->setScheduleLessonTypeId($scheduleNote['schedule_lesson_type_id']);
            $newSchedule->setScheduleClassroomId($scheduleNote['schedule_classroom_id']);
            $newSchedule->setUserId($scheduleNote['user_id']);
            $newSchedule->setScheduleSubjectId($scheduleNote['subject_id']);
            $newSchedule->setScheduleGroupId($groupId);
            $this->em->persist($newSchedule);
        }
        $this->em->flush();

        $this->deleteShedule($oldSchedule);

        return $this->json([
            'status' => 'ok',
        ]);
    }

    // Получение расписания для группы 
    #[Route(path: '/request/get/schedule/{groupId}', name: 'get_schedule')] 
    function getSchedule($groupId) {

        $schedule = $this->em->getRepository(Schedule::class)->findBy(['schedule_group_id' => $groupId]);
        
        return $this->json([
            'status' => 'ok',
            'schedule' => $schedule
        ]);
    }








    function deleteShedule($schedule) {
        foreach ($schedule as $item) {
            $this->em->remove($item);
        }
        $this->em->flush();
    }
}
