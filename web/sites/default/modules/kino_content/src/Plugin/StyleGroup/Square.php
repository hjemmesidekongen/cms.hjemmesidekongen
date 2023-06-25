<?php

namespace Drupal\kino_content\Plugin\StyleGroup;

use Drupal\image_style_generator\Annotation\StyleGroup;
use Drupal\image_style_generator\StyleGroupBase;
use Drupal\image_style_generator\StyleViewModeInterface;

/**
 * @StyleGroup(
 *  id = "square",
 *  title = "Square",
 *  view_modes = {
 *    "width_50",
 *    "width_33"
 *  }
 * )
 */
class Square extends StyleGroupBase {

  public function getCrop(string $breakpoint, int $width, int $multiplier, ?StyleViewModeInterface $view_mode = NULL): array {
    $calculated_width = $width * $multiplier;
    return [
      'width' => $calculated_width,
      'height' => $calculated_width
    ];
  }

}
