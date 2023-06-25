<?php

namespace Drupal\kino_content\Plugin\Transform\Field;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\kino_content\Plugin\Field\FieldFormatter\IMDBIDFormatter;
use Drupal\transform_api\FieldTransformBase;

/**
 * @FieldTransform(
 *  id = "imdb_id",
 *  label = @Translation("IMDB ID"),
 *  field_types = {
 *    "link"
 *  }
 * )
 */
class IMDBTransform extends FieldTransformBase {

  /**
   * @inheritDoc
   */
  public function transformElements(FieldItemListInterface $items, $langcode): array {
    $values = [];
    /** @var \Drupal\link\LinkItemInterface $item */
    foreach ($items as $delta => $item) {
      if (!empty($item->getValue())) {
        $values[] = IMDBIDFormatter::getIMDBID($item->getValue());
      }
    }
    return $values;
  }

}
