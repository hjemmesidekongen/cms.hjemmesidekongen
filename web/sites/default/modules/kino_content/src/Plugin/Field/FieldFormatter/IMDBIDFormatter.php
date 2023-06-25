<?php

namespace Drupal\kino_content\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\kino_content\IMDB;

/**
 * Plugin implementation of the 'imdb_id' formatter.
 *
 * @FieldFormatter(
 *   id = "imdb_id",
 *   label = @Translation("IMDB ID"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class IMDBIDFormatter extends FormatterBase {

  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    /** @var \Drupal\link\LinkItemInterface $item */
    foreach ($items as $delta => $item) {
      $imdbId = static::getIMDBID($item->getValue());
      $element[$delta] = [
        '#plain_text' => $imdbId,
      ];
    }
    return $element;
  }

  public static function getIMDBID(array $value) {
    $imdbId = NULL;
    if (!empty($value) && !empty($value['uri'])) {
      $imdbId = IMDB::UrlToId($value['uri']);
    }
    return $imdbId;
  }

}
