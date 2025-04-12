<?php

namespace App\Service;

class BreadcrumbsGenerator
{
    private array $items = [];

    public function addItem(string $title, ?string $url = null): void
    {
        $this->items[] = ['title' => $title, 'url' => $url];
    }

    public function getItems(): array
    {
        return $this->items;
    }
}