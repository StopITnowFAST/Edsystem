<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Form\RegistrationFormType;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Request;

class TableController extends AbstractController {

    function createPagination($page, $rep, $paginationSize) {
        $pagination = [];
        $limit = $paginationSize; 
        $offset = $paginationSize * ($page - 1);
        $totalNotes = $rep->getTotalPages();
        $totalPages = ceil($totalNotes / $paginationSize);

        $pagination['data'] = $rep->getTableData($offset, $limit);
        $pagination['totalNotes'] = $totalNotes;
        $pagination['row'] = $this->getPaginationRow($totalPages, $page, $paginationSize);

        return $pagination;
    }

    function getPaginationRow($totalPages, $page, $paginationSize) {
        $totalPages = ($totalPages == 0) ? 1 : $totalPages;        
        $elementsOnScreen = 5;
        $offset = ($page-($elementsOnScreen/2) < 0) ? 0 : $page-($elementsOnScreen/2);
        $range = range(1, $totalPages);
        $pagRow = array_slice($range, $offset, $elementsOnScreen);

        // Создание пограничных элементов
        if($pagRow[0] > 1) {
            array_unshift($pagRow, '...');
            array_unshift($pagRow, 1);
        }
        if(end($pagRow) < $totalPages) {
            array_push($pagRow, '...');
            array_push($pagRow, $totalPages);
        }

        // Создание ряда видимых элементов
        foreach($pagRow as $item) {
            $formattedItem['text'] = $item;
            $formattedItem['args'] =  ($item == '...') ? false : "/$page";
            $formattedRow[] = $formattedItem;
        }

        // Добавление стрелочек
        array_unshift($formattedRow, [
            'text' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M14.5 7L9.5 12L14.5 17" stroke="#636A71" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>',
            'args' => ($page == 1) ? false : "/" . ($page - 1),
        ]);
        array_push($formattedRow, [
            'text' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M9.5 7L14.5 12L9.5 17" stroke="#636A71" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>',
            'args' => ($page == $totalPages) ? false : "/" . ($page + 1),
        ]);

        return $formattedRow;
    }
}