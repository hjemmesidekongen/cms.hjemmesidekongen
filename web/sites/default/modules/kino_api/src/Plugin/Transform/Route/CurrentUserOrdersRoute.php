<?php

namespace Drupal\kino_api\Plugin\Transform\Route;

use Drupal\transform_api\Annotation\RouteTransform;
use Drupal\transform_api\Exception\RedirectTransformationException;
use Drupal\transform_api\RouteTransformBase;
use Drupal\transform_api\Transform\EntityTransform;
use Drupal\transform_api\Transform\TransformInterface;

/**
 * @RouteTransform(
 *  id = "kino_api.current.user.orders",
 *  title = "Current user orders"
 * )
 */
class CurrentUserOrdersRoute extends RouteTransformBase {

  public function transform(TransformInterface $transform): array {
    throw new RedirectTransformationException('kino_api.user.orders', ['user' => \Drupal::currentUser()->id()]);
  }

}
