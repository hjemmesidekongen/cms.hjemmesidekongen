<?php

namespace Drupal\kino_content\Plugin\Relewise\DataField;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\relewise\Annotation\DataField;
use Drupal\relewise\DataFieldBase;
use Relewise\Factory\DataValueFactory;
use Relewise\Models\DataValue;

/**
 * @DataField(
 *  id = "premiere",
 *  label = @Translation("Premiere date"),
 *  field_types = {
 *    "date",
 *    "datetime"
 *  }
 * )
 */
class PremiereDataField extends DataFieldBase {

  public function getDataValue(FieldItemListInterface $items, $langcode = NULL): ?DataValue {
    if ($items->isEmpty() || empty($items->first()) || empty($items->first()->getValue())) {
      $field = $items->getEntity()->get('field_unknown_future_premiere');
      if ($field->isEmpty() || empty($field->first()) || empty($field->first()->getValue()) || empty($field->first()->getValue()['value'])) {
        $dateformat = new DrupalDateTime('1971-01-01');
      } else {
        $dateformat = new DrupalDateTime('2080-01-01');
      }
    } else {
      $dateformat = new DrupalDateTime($items
        ->first()
        ->getValue()['value']);
    }
    return DataValueFactory::float($dateformat->getTimestamp());
  }

}
