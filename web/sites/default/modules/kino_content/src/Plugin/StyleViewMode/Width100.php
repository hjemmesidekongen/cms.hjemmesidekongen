<?php

namespace Drupal\kino_content\Plugin\StyleViewMode;

use Drupal\image_style_generator\Annotation\StyleViewMode;
use Drupal\image_style_generator\StyleViewModeBase;

/**
 * @StyleViewMode(
 *  id = "width_100",
 *  title = "Width 100%"
 * )
 */
class Width100 extends ContentRegionBase {

  public function calcWidth($breakpoint, $width): int {
    return $this->widths[$breakpoint] = parent::calcWidth($breakpoint, $width);
  }

}
