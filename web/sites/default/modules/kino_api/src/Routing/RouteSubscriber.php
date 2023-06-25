<?php
namespace Drupal\kino_api\Routing;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RouteSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RoutingEvents::ALTER] = 'alterRoutes';
    return $events;
  }

  /**
   * Alters existing routes.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route building event.
   */
  public function alterRoutes(RouteBuildEvent $event) {
    // Fetch the collection which can be altered.
    $collection = $event->getRouteCollection();
    // The event is fired multiple times so ensure that the user_page route
    // is available.
    if ($route = $collection->get('jwt_auth_issuer.jwt_auth_issuer_controller_generateToken')) {
      // As example add a new requirement.
      $route->setOption('_auth', [ 'jwt_auth', 'email_auth', 'cookie']);
    }
  }

}
