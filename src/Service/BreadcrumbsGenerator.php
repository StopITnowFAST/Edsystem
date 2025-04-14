<?php

namespace App\Service;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class BreadcrumbsGenerator
{
    private array $items = [];
    public function registerBreadcrumbs(array $breadArray, UrlGeneratorInterface $router) {
        foreach ($breadArray as $title => $routeInfo) {
            $url = null;
            
            if (is_array($routeInfo)) {
                // Если передан массив с именем маршрута и параметрами
                [$routeName, $params] = $routeInfo;
                $url = $router->generate($routeName, $params, UrlGeneratorInterface::ABSOLUTE_PATH);
            } elseif (is_string($routeInfo)) {
                // Если передано только имя маршрута
                $url = $router->generate($routeInfo);
            }
            // Если null - оставляем url как null (текущая страница)
            
            $this->items[] = ['title' => $title, 'url' => $url];
        }
        return $this->items;
    }

    public function getItems(): array
    {
        return $this->items;
    }
}