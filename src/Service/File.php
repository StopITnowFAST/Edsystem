<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class File
{
    public function __construct(
        private EntityManagerInterface $em
    ) {        
    }
    
    public function saveFile(
        $file,
        $friendlyUrl,
    ) {

    }
}