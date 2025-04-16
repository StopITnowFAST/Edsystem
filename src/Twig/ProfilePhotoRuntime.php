<?php

namespace App\Twig;

use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\VkUser;

class ProfilePhotoRuntime
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $em,
        \Psr\Log\LoggerInterface $logger
    ) {
        $logger->debug("ProfilePhotoRuntime initialized!");
    }


    public function getProfilePhoto(): string {
        $user = $this->security->getUser();
        if (!$user) {
            return "";
        }
        $vkUser = $this->em->getRepository(VkUser::class)->findOneBy(['user_id' => $user->getId()]);
        return $vkUser?->getAvatar() ?? "";
    }
}