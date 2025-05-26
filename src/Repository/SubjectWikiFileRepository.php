<?php

namespace App\Repository;

use App\Entity\SubjectWikiFile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SubjectWikiFile>
 */
class SubjectWikiFileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubjectWikiFile::class);
    }
    
    public function getStudentFiles($userId, $wikiId) {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT * FROM `subject_wiki_file` swf 
            JOIN `file` f ON swf.file_id = f.id
            WHERE swf.file_type = 'student'
            AND f.created_by = $userId
            AND swf.wiki_id = $wikiId
        ";
        $resultSet = $conn->executeQuery($sql);        
        return $resultSet->fetchAllAssociative();
    }
}
