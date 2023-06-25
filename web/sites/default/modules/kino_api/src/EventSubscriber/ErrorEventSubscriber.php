<?php

namespace Drupal\kino_api\EventSubscriber;

use Drupal\kino_api\Exception\ApiException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ErrorEventSubscriber implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    $events[KernelEvents::EXCEPTION][] = ['onException', 60];
    return $events;
  }

  /**
   * If exception is instance of ApiException, return a JsonResponse with an
   * error message and additional fields.
   */
  public function onException(ExceptionEvent $event) {
    $throwableException = $event->getThrowable();
    if ($throwableException instanceof ApiException) {
      $response = new \stdClass();
      $response->error = $throwableException->getMessage();
      $response->resultCode = $throwableException->getResultCode();
      foreach ($throwableException->getAdditionalFields() as $key => $value) {
        $response->$key = $value;
      }
      $event->setResponse(new JsonResponse($response));
    }
  }

}
