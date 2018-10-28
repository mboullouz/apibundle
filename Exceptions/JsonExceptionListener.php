<?php

namespace Axescloud\ApiBundle\Execeptions;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class JsonExceptionListener {

    public function onKernelException(GetResponseForExceptionEvent $event) {
        $exception = $event->getException();
        if ($exception instanceof QueryParserException || $exception instanceof BadRequestException) {
            $response = new JsonResponse($exception->getMessage());
            $response->headers->set('Content-Type', 'application/json; charset=UTF-8');
            $event->setResponse($response);
        }
    }
}
