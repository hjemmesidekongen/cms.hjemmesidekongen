<?php

namespace Drupal\kino_api\Plugin\Transform\Route;

use Drupal\transform_api\Annotation\RouteTransform;
use Drupal\transform_api\RouteTransformBase;
use Drupal\transform_api\Transform\EntityTransform;
use Drupal\transform_api\Transform\TransformInterface;

/**
 * @RouteTransform(
 *  id = "kino_api.user.orders",
 *  title = "User orders"
 * )
 */
class UserOrdersRoute extends RouteTransformBase {

  public function transform(TransformInterface $transform): array {
    $transformation = [
      'current_orders' => [],
      'past_orders' => []
    ];

    $ids = [28, 48, 67];
    foreach ($ids as $id) {
      $transformation['current_orders'][] = $this->dummyOrder($id);
      $transformation['past_orders'][] = $this->dummyOrder($id);
    }

    return $transformation;
  }

  protected function dummyOrder($id) {
    /** @var \Drupal\node\Entity\Node $movie */
    $movie = \Drupal::entityTypeManager()->getStorage('node')->load($id);
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $poster_field */
    $poster_field = $movie->get('field_poster');
    foreach ($poster_field->referencedEntities() as $entity) {
      $poster = $entity;
    }


    /** @var \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter */
    $dateFormatter = \Drupal::service('date.formatter');
    $order = [
      "type" => "order",
      "id" => $id,
      "label" => $movie->label(),
      "date" => $dateFormatter->format(time(), 'html_date'),
      "cinema" => 'CineMaxx København',
      "description" => 'Sal 5, Sæde 14<br>Ordrenummer: 14568747',
      "download_link" => 'https://www.google.com/',
      "transform_mode" => "summary",
      "langcode" => "da",
    ];
    if (isset($poster)) {
      $order['field_poster'] = EntityTransform::createFromEntity($poster, 'poster');
    }

    return $order;
  }

}
