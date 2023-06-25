<?php

namespace Drupal\kino_content\Plugin\Transform\Field;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\kino_content\Plugin\Field\FieldFormatter\IMDBIDFormatter;
use Drupal\transform_api\FieldTransformBase;

/**
 * @FieldTransform(
 *  id = "hidden",
 *  label = @Translation("Hidden"),
 *  field_types = {
 *    "string",
 *    "string_long",
 *    "list_string",
 *    "integer",
 *    "float",
 *    "telephone",
 *    "email"
 *  }
 * )
 */
class HiddenTransform extends FieldTransformBase {

  /**
   * @inheritDoc
   */
  public function transformElements(FieldItemListInterface $items, $langcode): array {
    return [];
  }

}
