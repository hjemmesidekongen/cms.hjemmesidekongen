<?php

namespace Drupal\kino_content\Plugin\StyleGroup;

use Drupal\image_style_generator\StyleGroupBase;
use Drupal\image_style_generator\StyleViewModeInterface;

/**
 * @StyleGroup(
 *  id = "poster",
 *  title = "Poster",
 *  view_modes = {
 *  }
 * )
 */
class Poster  extends StyleGroupBase {

  public function getCrop(string $breakpoint, int $width, int $multiplier, ?StyleViewModeInterface $view_mode = NULL): array {
    // TODO: This is only the desktop size
    $width = 356;
    $calculated_width = $width * $multiplier;
    return [
      'width' => $calculated_width,
      'height' => $calculated_width / 2 * 3
    ];
  }

}
