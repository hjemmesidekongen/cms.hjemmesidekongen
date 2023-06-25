<?php
namespace Drupal\kino_content\OverviewFields;

use Drupal\entity_overview\OverviewFields\OverviewFieldBase;

class ActiveField extends OverviewFieldBase {
  public function __construct() {
    parent::__construct('active', t('Active in cinemas'));
  }

  public function getWidgets(): array {
    return ['checkbox' => t('Single on/off checkbox')];
  }
}
