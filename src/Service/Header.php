<?php

namespace App\Service;

use App\Entity\HeaderMenu;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class Header
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
    ) {        
    }

    public function getProfilePhoto(): string {
        $user = $this->security->getUser();
        if (!$user) {
            return "";
        }
        $vkUser = $this->em->getRepository(VkUser::class)->findOneBy(['user_id' => $user->getId()]);
        return $vkUser?->getAvatar() ?? "";
    }
    
    public function getHeaderMenu(\Symfony\Component\Security\Core\User\UserInterface $user = null) {
        $menuModerator = $this->em->getRepository(HeaderMenu::class)->findBy(['status' => 1], ['place_order' => 'asc']);
        $menuTeacher = $this->em->getRepository(HeaderMenu::class)->findBy([
                'status' => 1,
                'is_for_teacher' => 1,
            ], ['place_order' => 'asc']);
        $menuData['moderator'] = $this->buildTree($menuModerator);
        $menuData['teacher'] = $this->buildTree($menuTeacher);

        return $menuData;
    }

    function buildTree(array $items): array {
        $grouped = [];
        foreach ($items as $item) {
            $parentId = $item->getParentId() ?? 0;
            $grouped[$parentId][] = $item;
        }
        return $this->buildBranch($grouped, 0);
    }

    private function buildBranch(array &$grouped, int $parentId): array {
        $branch = [];
        if (!isset($grouped[$parentId])) {
            return $branch;
        }
        usort($grouped[$parentId], function($a, $b) {
            return $a->getPlaceOrder() <=> $b->getPlaceOrder();
        });
        foreach ($grouped[$parentId] as $item) {
            $node = [
                'id' => $item->getId(),
                'name' => $item->getName(),
                'url' => $item->getUrl(),
                'order' => $item->getPlaceOrder(),
                'role' => ($item->isForTeacher()) ? 'ROLE_TEACHER' : 'ROLE_MODERATOR',
                'original' => $item,
            ];
            $children = $this->buildBranch($grouped, $item->getId());
            if ($children) {
                $node['children'] = $children;
            }
            $branch[] = $node;
        }
        return $branch;
    }

    public function canUserAccessThisPage($path, $security) {
        $headerItem = $this->em->getRepository(HeaderMenu::class)->findOneBy(['url' => $path]);
        // var_dump($headerItem?->getName());
        if ($headerItem) {
            if ($headerItem->isForTeacher() && $security->isGranted('ROLE_TEACHER')) {
                // Если страница для преподавателя, и пользователь является преподавателем
                return true;
            } else if (!$headerItem->isForTeacher() && $security->isGranted('ROLE_MODERATOR')) {
                // Если страница для модератора, и пользователь является модератором
                return true;
            } else {
                return false;
            }
        }
        return false;
    }
}