<?php

namespace Drupal\hjemmesidekongen_api\Plugin\Transform\Block;

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

    // Navigation.
    $mainMenu = $this->menuBlockTransform->createInstance('menu', ['menu' => 'main'] + $configs);

    return [
      'navigation' => [
        "main" => $mainMenu->transform(),
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
        'config:system.menu.main',
      ];
  }

}
