<?php

namespace Drupal\hjemmesidekongen_api\Plugin\Transform\Block;

use Drupal\atoms\Transform\AtomTransform;
use Drupal\transform_api\Annotation\BlockTransform;
use Drupal\transform_api\BlockTransformBase;
use Drupal\transform_api\BlockTransformManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @BlockTransform(
 *   id = "site_settings",
 *   title = "SiteSettings"
 * )
 */
class SiteSettings extends BlockTransformBase {

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
    $footerFirstMenu = $this->menuBlockTransform->createInstance('menu', ['menu' => 'footer-first'] + $configs);
    $footerSecondMenu = $this->menuBlockTransform->createInstance('menu', ['menu' => 'footer-second'] + $configs);
    $footerThirdMenu = $this->menuBlockTransform->createInstance('menu', ['menu' => 'footer-third'] + $configs);
    $copyrightMenu = $this->menuBlockTransform->createInstance('menu', ['menu' => 'copyright'] + $configs);

    return [
      'site_information' => [
        "logo_text" => theme_get_setting('logo_text'),
        "email_address" => theme_get_setting('email_address'),
        "site_name" => theme_get_setting('site_name'),
      ],
      'theme' => [
        "primary_hex_25" => theme_get_setting('primary_hex_25'),
        "primary_hex_50" => theme_get_setting('primary_hex_50'),
        "primary_hex_100" => theme_get_setting('primary_hex_100'),
        "primary_hex_200" => theme_get_setting('primary_hex_200'),
        "primary_hex_300" => theme_get_setting('primary_hex_300'),
        "primary_hex_400" => theme_get_setting('primary_hex_400'),
        "primary_hex_500" => theme_get_setting('primary_hex_500'),
        "primary_hex_600" => theme_get_setting('primary_hex_600'),
        "primary_hex_700" => theme_get_setting('primary_hex_700'),
        "primary_hex_800" => theme_get_setting('primary_hex_800'),
        "primary_hex_900" => theme_get_setting('primary_hex_900'),
      ],
      'navigation' => [
        "main" => $mainMenu->transform(),
        "footer_first" => $footerFirstMenu->transform(),
        "footer_second" => $footerSecondMenu->transform(),
        "footer_third" => $footerThirdMenu->transform(),
        "copyright" => $copyrightMenu->transform(),
      ],
      "footer" => [
        "footer_navigation_first_heading" => theme_get_setting('footer_navigation_first_heading'),
        "footer_navigation_second_heading" => theme_get_setting('footer_navigation_second_heading'),
        "footer_navigation_third_heading" => theme_get_setting('footer_navigation_third_heading'),
      ]
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
        'config:system.menu.footer-first',
        'config:system.menu.footer-second',
        'config:system.menu.footer-third',
        'config:system.menu.copyright',
      ];
  }

}
