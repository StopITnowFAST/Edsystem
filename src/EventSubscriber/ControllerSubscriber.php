<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use App\Service\Redirect;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ControllerSubscriber implements EventSubscriberInterface
{
    function __construct(
        private Redirect $redirect,
        private RouterInterface $router,
        private HttpKernelInterface $httpKernel,
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
        $pathWithQuery = $request->getRequestUri();

        // Попытка найти редирект в базе
        if ($this->redirect->isNeedToRedirect($pathWithQuery)) {
            $response = $this->redirect->redirectFrom($pathWithQuery);
            $event->setController(function() use ($response) {
                return $response;
            });
        }
    }
}