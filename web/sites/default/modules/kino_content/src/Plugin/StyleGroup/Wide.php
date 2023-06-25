<?php

namespace Drupal\kino_content\Plugin\StyleGroup;

use Drupal\image_style_generator\StyleGroupBase;
use Drupal\image_style_generator\StyleViewModeInterface;

/**
 * @StyleGroup(
 *  id = "wide",
 *  title = "Wide",
 *  view_modes = {
 *    "full_width"
 *  }
 * )
 */
class Wide extends StyleGroupBase {

  public function getCrop(string $breakpoint, int $width, int $multiplier, ?StyleViewModeInterface $view_mode = NULL): array {
    $calculated_width = $width * $multiplier;
    return [
      'width' => $calculated_width,
      'height' => $calculated_width / 4
    ];
  }

}
