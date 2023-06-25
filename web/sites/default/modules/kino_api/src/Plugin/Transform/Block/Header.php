<?php

namespace Drupal\kino_api\Plugin\Transform\Block;

use Drupal\transform_api\Annotation\BlockTransform;
use Drupal\transform_api\BlockTransformBase;
use Drupal\transform_api\BlockTransformManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @BlockTransform(
 *   id = "header",
 *   title = "Header"
 * )
 */
class Header extends BlockTransformBase {

  private BlockTransformManager $menuBlockTransform;

  /**
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\transform_api\BlockTransformManager $menuBlockTransform
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BlockTransformManager $menuBlockTransform) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->menuBlockTransform = $menuBlockTransform;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.transform_api.block_transform')
    );
  }

  public function transform() {
    $configs = [
      'id' => 'menu',
      'expand_all_items' => TRUE,
      'level' => 1,
      'depth' => 9,
    ];

    // Kino headers
    $kinoMenu = $this->menuBlockTransform->createInstance('menu', ['menu' => 'kino-main'] + $configs);
    $kinoTopMenu = $this->menuBlockTransform->createInstance('menu', ['menu' => 'kino-topmenu'] + $configs);

    // Streaming Guide headers
    $streamingGuideMenu = $this->menuBlockTransform->createInstance('menu', ['menu' => 'streaming-guide-main'] + $configs);
    $streamingGuideTopMenu = $this->menuBlockTransform->createInstance('menu', ['menu' => 'streaming-guide-topmenu'] + $configs);

    return [
      'kino' => [
        "menu" => $kinoMenu->transform(),
        "topmenu" => $kinoTopMenu->transform(),
      ],
      'streaming_guide' => [
        "menu" => $streamingGuideMenu->transform(),
        "topmenu" => $streamingGuideTopMenu->transform(),
      ],
    ];
  }

  /**
   * @inheritDoc
   */
  public function getCacheTags() {
    return parent::getCacheTags()
      + [
        'languages',
        'config:system.menu.kino-main',
        'config:system.menu.kino-topmenu',
        'config:system.menu.streaming-guide-main',
        'config:system.menu.streaming-guide-topmenu',
      ];
  }

}
