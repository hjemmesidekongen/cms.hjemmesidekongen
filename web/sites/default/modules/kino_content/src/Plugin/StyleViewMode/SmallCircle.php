<?php

namespace Drupal\kino_content\Plugin\StyleViewMode;

use Drupal\image_style_generator\StyleViewModeBase;

/**
 * @StyleViewMode(
 *  id = "small_circle",
 *  title = "Small circle"
 * )
 */
class SmallCircle extends ContentRegionBase {

  public function calcWidth($breakpoint, $width): int {
    if ($this->isMobile($breakpoint)) {
      $width = 49;
    } else {
      $width = 65;
    }
    return $this->widths[$breakpoint] = $width;
  }

}
