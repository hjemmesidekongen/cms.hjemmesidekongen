<?php
namespace Drupal\kino_content\OverviewFields;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\entity_overview\OverviewFields\OverviewFieldBase;
use Drupal\entity_overview\OverviewFilter;

class PremiereField extends OverviewFieldBase {
  public function __construct() {
    parent::__construct('premiere', t('Premiere date'));
  }

  /**
   * @inheritDoc
   */
  public function getWidgets(): array {
    return ['radios' => t('Radios'), 'select' => t('Select list')];
  }

  /**
   * @inheritDoc
   */
  public function getFieldFormElement(OverviewFilter $filter): array {
    $options = [
      '' => t('Anytime'),
      'future' => t('In the future'),
      'past' => t('In the past')
    ];
    return parent::getFieldFormElement($filter) + ['#options' => $options];
  }
}
