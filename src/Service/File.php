<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Entity\File as FileEntity;

class File
{
    private string $uploadDirectory;    

    public function __construct(
        private EntityManagerInterface $em,
    ) {
        $this->uploadDirectory = $_ENV['USER_FILE_PATH'];
        $this->em = $em;
    }

    public function handleUploadedFiles($files, int $userId) {
        foreach ($files as $file) {
            $this->handleUploadedFile($file, $userId);
        }
    }

    public function handleUploadedFile(UploadedFile $file, int $userId) {
        // Сохраняем файл на диск
        $fileInfo = $this->saveFileToDisk($file);
        
        // Создаем и сохраняем сущность файла
        return $this->createFileEntity(
            $fileInfo,
            $userId
        );
    }

    private function saveFileToDisk(UploadedFile $file): array
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $fileExtension = $file->guessExtension();
        $realFileName = uniqid('file_', true).'.'.$fileExtension;
        $fileSize = $file->getSize();

        $file->move($this->uploadDirectory, $realFileName);

        return [
            'file_name' => $originalFilename,
            'extension' => $fileExtension,
            'size' => $fileSize,
            'real_file_name' => $realFileName
        ];
    }

    private function createFileEntity(array $fileInfo, int $userId) {
        $fileEntity = new FileEntity();
        $fileEntity->setFileName($fileInfo['file_name']);
        $fileEntity->setExtension($fileInfo['extension']);
        $fileEntity->setSize($fileInfo['size']);
        $fileEntity->setRealFileName($fileInfo['real_file_name']);
        $fileEntity->setCreatedBy($userId);
        
        $this->em->persist($fileEntity);
        $this->em->flush();

        return $fileEntity;
    }
}