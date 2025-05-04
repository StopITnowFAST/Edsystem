<?php

namespace App\Repository;

use App\Entity\Schedule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
                s.week_number,
                s.schedule_day,
                st.lesson_number,
                st.start_time,
                st.end_time,
                slt.name as lesson_type,
                sc.name as classroom,
                ss.name as subject,
                g.code,
                t.last_name,
                t.first_name
            FROM `schedule` s 
            JOIN `schedule_time` st ON st.id = s.schedule_time_id
            JOIN `schedule_lesson_type` slt ON slt.id = s.schedule_lesson_type_id
            JOIN `schedule_classroom` sc ON sc.id = s.schedule_classroom_id
            JOIN `schedule_subject` ss ON ss.id = s.schedule_subject_id
            JOIN `group` g ON g.id = s.schedule_group_id
            JOIN `teacher` t ON t.user_id = s.user_id
            WHERE s.schedule_group_id = $groupId
        ";
        $resultSet = $conn->executeQuery($sql);        
        return $resultSet->fetchAllAssociative();
    }
}
