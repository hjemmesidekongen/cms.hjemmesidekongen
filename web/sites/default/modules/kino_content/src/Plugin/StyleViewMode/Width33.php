<?php

namespace Drupal\kino_content\Plugin\StyleViewMode;

use Drupal\image_style_generator\Annotation\StyleViewMode;
use Drupal\image_style_generator\StyleViewModeBase;

/**
 * @StyleViewMode(
 *  id = "width_33",
 *  title = "Width 33%"
 * )
 */
class Width33 extends ContentRegionBase {

  public function calcWidth($breakpoint, $width): int {
    if ($this->isMobile($breakpoint)) {
      return $this->widths[$breakpoint] = parent::calcWidth($breakpoint, $width);
    } else {
      return $this->widths[$breakpoint] = round((parent::calcWidth($breakpoint, $width) / 3) - ($this->getGutterWidth($breakpoint) * 2));
    }
  }

}
