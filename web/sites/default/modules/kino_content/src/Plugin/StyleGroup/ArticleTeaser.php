<?php

namespace Drupal\kino_content\Plugin\StyleGroup;

use Drupal\image_style_generator\Annotation\StyleGroup;
use Drupal\image_style_generator\StyleGroupBase;
use Drupal\image_style_generator\StyleViewModeInterface;

/**
 * @StyleGroup(
 *  id = "article_teaser",
 *  title = "Article teaser",
 *  view_modes = {
 *    "full_width",
 *    "width_100",
 *    "width_66",
 *    "width_50",
 *    "width_33",
 *  }
 * )
 */
class ArticleTeaser extends StyleGroupBase {

  public function getCrop(string $breakpoint, int $width, int $multiplier, ?StyleViewModeInterface $view_mode = NULL): array {
    $calculated_width = $width * $multiplier;
    $is_mobile = $this->isMobile($breakpoint);

    if (!$is_mobile) {
      $calculated_width = $width / 3 * $multiplier;
    }

    return [
      'width' => $calculated_width,
      'height' => $calculated_width / 16 * 9
    ];
  }

  protected function isMobile($breakpoint) {
    return in_array($breakpoint, ['xs', 'xxs']);
  }

}
