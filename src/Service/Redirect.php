<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Redirect as RedirectEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;

class Redirect
{
    public function __construct(
        private EntityManagerInterface $em
    ) {        
    }
    
    public function isNeedToRedirect($path) {
        $redirect = $this->em->getRepository(RedirectEntity::class)->findOneBy(['redirect_from' => $path]);
        return isset($redirect);
    }

    public function redirectFrom($path) {
        $redirect = $this->em->getRepository(RedirectEntity::class)->findOneBy(['redirect_from' => $path]);
        $redirectPath = $redirect->getRedirectTo();
        return new RedirectResponse($redirectPath);
    }

    public function getTargetUrl($path) {
        $redirect = $this->em->getRepository(RedirectEntity::class)->findOneBy(['redirect_from' => $path]);
        $redirectPath = $redirect->getRedirectTo();
        return new RedirectResponse($redirectPath);
    }
}