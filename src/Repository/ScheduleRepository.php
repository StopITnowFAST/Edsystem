<?php

namespace App\Repository;

use App\Entity\Schedule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * @extends ServiceEntityRepository<Schedule>
 */
class ScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Schedule::class);
    }

     
    public function findScheduleByGroupId($groupId) {
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = "
            SELECT 
                st.lesson_number,
                st.start_time,
                st.end_time,
                slt.name as lesson_type,
                sc.name as classroom,
                ss.name as subject,
                g.code,
                t.last_name,
                t.first_name,
                s.week_number,
                s.schedule_day,
                g.ed_starts_first,
                g.ed_starts_second,
                s.user_id
            FROM `schedule` s 
            JOIN `schedule_time` st ON st.id = s.schedule_time_id
            JOIN `schedule_lesson_type` slt ON slt.id = s.schedule_lesson_type_id
            JOIN `schedule_classroom` sc ON sc.id = s.schedule_classroom_id
            JOIN `schedule_subject` ss ON ss.id = s.schedule_subject_id
            JOIN `group` g ON g.id = s.schedule_group_id
            JOIN `teacher` t ON t.user_id = s.user_id
            WHERE s.schedule_group_id = :group_id
        ";
        
        $resultSet = $conn->executeQuery($sql, ['group_id' => $groupId]);
        $schedule = $resultSet->fetchAllAssociative();
        
        // Остальной код без изменений
        $currentDate = new \DateTime();
        
        foreach ($schedule as &$item) {
            $startsFirst = new \DateTime($item['ed_starts_first']);
            $startsSecond = $item['ed_starts_second'] ? new \DateTime($item['ed_starts_second']) : null;
            
            if ($currentDate >= $startsFirst && ($startsSecond === null || $currentDate < $startsSecond)) {
                $baseDate = clone $startsFirst;
            } else {
                $baseDate = clone $startsSecond;
            }
            
            $diff = $currentDate->diff($baseDate)->days;
            $weeks = floor($diff / 14);
            
            $lessonDate = clone $baseDate;
            $daysToAdd = ($weeks * 14) + (($item['week_number'] - 1) * 7 + ($item['schedule_day'] - 1));
            $lessonDate->add(new \DateInterval('P'.$daysToAdd.'D'));
            
            $item['date'] = $lessonDate->format('d.m');
        }
        
        return $schedule;
    }
    
    public function findTeacherSchedule($userId) {
        $conn = $this->getEntityManager()->getConnection();
        
        // 1. Получаем все группы, где преподает этот преподаватель
        $sql = "
            SELECT DISTINCT g.id
            FROM `schedule` s
            JOIN `group` g ON g.id = s.schedule_group_id
            WHERE s.user_id = :user_id
        ";
        $groups = $conn->executeQuery($sql, ['user_id' => $userId])->fetchAllAssociative();
        
        $result = [];
        
        // 2. Для каждой группы получаем расписание
        foreach ($groups as $group) {
            $groupSchedule = $this->findScheduleByGroupId($group['id']);
            
            // 3. Фильтруем только занятия этого преподавателя
            foreach ($groupSchedule as $lesson) {
                if ($lesson['user_id'] == $userId) {
                    $result[] = $lesson;
                }
            }
        }
        
        // 4. Сортируем результат по дате и номеру пары
        usort($result, function($a, $b) {
            $dateCompare = strcmp($a['date'], $b['date']);
            return $dateCompare !== 0 ? $dateCompare : $a['lesson_number'] - $b['lesson_number'];
        });
        
        return $result;
    }
}
