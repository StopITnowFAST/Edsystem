<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Redirect as RedirectEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;

class Help
{
    public function __construct(
        private EntityManagerInterface $em
    ) {        
    }
    
    function generateRandomString(int $length = 10): string {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $result = '';        
        for ($i = 0; $i < $length; $i++) {
            $result .= $characters[random_int(0, strlen($characters) - 1)];
        }        
        return $result;
    }
}