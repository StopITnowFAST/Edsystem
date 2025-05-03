<?php

namespace App\Repository;

use App\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function markMessagesAsRead(int $fromUserId, int $toUserId): void
    {
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = '
            UPDATE message 
            SET is_read = true 
            WHERE from_user_id = :fromUserId 
            AND to_user_id = :toUserId 
            AND is_read = false
        ';
        
        $stmt = $conn->prepare($sql);
        $stmt->executeQuery([
            'fromUserId' => $fromUserId,
            'toUserId' => $toUserId
        ]);
    }
}
