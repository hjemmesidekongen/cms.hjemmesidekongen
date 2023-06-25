<?php

namespace Drupal\kino_content\Plugin\StyleViewMode;

use Drupal\image_style_generator\StyleViewModeBase;

class ContentRegionBase extends StyleViewModeBase {

  protected int $maxContainerSize = 1200;
  protected int $desktopGutter = 44;
  protected int $mobileGutter = 20;

  public function calcWidth($breakpoint, $width): int {
    $width = min($this->maxContainerSize, $width);
    return $this->widths[$breakpoint] = $width - ($this->getGutterWidth($breakpoint) * 2);
  }

  protected function getGutterWidth($breakpoint) {
    switch ($breakpoint) {
      case 'md':
        return $this->mobileGutter;
      default:
        return $this->desktopGutter;
    }
  }

  protected function isMobile($breakpoint) {
    return in_array($breakpoint, ['xs', 'xxs']);
  }

  protected function isTablet($breakpoint) {
    return in_array($breakpoint, ['md', 'sm', 'xs', 'xxs']);
  }

}
