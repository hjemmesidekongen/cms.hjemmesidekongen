<?php

namespace Drupal\kino_content\Plugin\StyleViewMode;

use Drupal\image_style_generator\StyleViewModeBase;

/**
 * @StyleViewMode(
 *  id = "big_circle",
 *  title = "Big circle"
 * )
 */
class BigCircle extends ContentRegionBase {

  public function calcWidth($breakpoint, $width): int {
    if ($this->isMobile($breakpoint)) {
      $width = 160;
    } else {
      $width = 320;
    }
    return $this->widths[$breakpoint] = $width;
  }

}
