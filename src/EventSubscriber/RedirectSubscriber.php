<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use App\Service\Redirect;
use App\Service\Header;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Bundle\SecurityBundle\Security;

class RedirectSubscriber implements EventSubscriberInterface
{
    function __construct(
        private Redirect $redirect,
        private Header $header,
        private RouterInterface $router,
        private HttpKernelInterface $httpKernel,
        private Security $security
    ) {
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    public function onKernelController(ControllerEvent $event) {
        // Получаем строку запроса
        $request = $event->getRequest();
        $path = $request->getPathInfo();
        
        // Проверка доступа к странице
        if (!$this->header->canUserAccessThisPage($path, $this->security)) {
            // die;
            $response = new Response('Доступ запрещен', 403);
            return $response;
        }

        // Попытка найти редирект в базе
        if ($this->redirect->isNeedToRedirect($path)) {
            $response = $this->redirect->redirectFrom($path);
            $event->setController(function() use ($response) {
                return $response;
            });
        }

        // Просмотр остальных маршрутов
        try {
            $this->router->match($path);
        } catch (\Symfony\Component\Routing\Exception\ResourceNotFoundException $e) {
            $response = new Response('Страница не найдена', 404);
            return $response;
        }
    }
}