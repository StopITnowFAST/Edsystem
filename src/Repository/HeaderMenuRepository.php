<?php

namespace App\Repository;

use App\Entity\HeaderMenu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HeaderMenu>
 */
class HeaderMenuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HeaderMenu::class);
    }
    
    public function findAllItems() {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT 
                header_menu.id,
                parent.name AS parent_name,
                header_menu.item_level,
                header_menu.name,
                header_menu.url,
                header_menu.place_order,
                status.name AS status
            FROM 
                header_menu
            LEFT JOIN 
                header_menu parent ON header_menu.parent_id = parent.id
            LEFT JOIN 
                status ON header_menu.status = status.id
        ";
        $resultSet = $conn->executeQuery($sql);        
        return $resultSet->fetchAllAssociative();
    }
}
