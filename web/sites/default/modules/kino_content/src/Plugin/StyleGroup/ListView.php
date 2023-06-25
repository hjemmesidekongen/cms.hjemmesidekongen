<?php

namespace Drupal\kino_content\Plugin\StyleGroup;

use Drupal\image_style_generator\StyleGroupBase;
use Drupal\image_style_generator\StyleViewModeInterface;

/**
 * @StyleGroup(
 *  id = "list_view",
 *  title = "List View",
 *  view_modes = {
 *  }
 * )
 */
class ListView extends StyleGroupBase {

  public function getCrop(string $breakpoint, int $width, int $multiplier, ?StyleViewModeInterface $view_mode = NULL): array {
    return [
      'width' => 365 * $multiplier,
      'height' => 200 * $multiplier
    ];
  }

}
