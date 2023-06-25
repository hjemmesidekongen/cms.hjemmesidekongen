<?php

namespace Drupal\kino_api\Plugin\Transform\Block;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\transform_api\BlockTransformBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;

/**
 * @BlockTransform(
 *  id = "universe",
 *  title = "Universe",
 * )
 */
class UniverseBlock extends BlockTransformBase {

  public static string $KINO = 'kino';

  public static string $STREAMING_GUIDE = 'streamingguide';

  protected RouteMatchInterface $routeMatch;

  protected LoggerChannelInterface $logger;

  protected TitleResolverInterface $titleResolver;

  protected RequestStack $requestStack;

  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a new SystemBreadcrumbBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The breadcrumb manager.
   * @param \Drupal\Core\Controller\TitleResolverInterface $titleResolver
   *   Title resolver.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   Logger.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Request stack.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TitleResolverInterface $titleResolver, RouteMatchInterface $routeMatch, LoggerChannelFactoryInterface $logger, RequestStack $requestStack, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->titleResolver = $titleResolver;
    $this->requestStack = $requestStack;
    $this->entityTypeManager = $entityTypeManager;
    $this->routeMatch = $routeMatch;
    $this->logger = $logger->get('kino');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('title_resolver'),
      $container->get('current_route_match'),
      $container->get('logger.factory'),
      $container->get('request_stack'),
      $container->get('entity_type.manager')
    );
  }

  public function transform() {
    $cacheMetadata = new CacheableMetadata();
    $cacheMetadata->addCacheContexts(['route'])->addCacheTags(['languages']);

    $request = $this->requestStack->getCurrentRequest();
    $status = $request->attributes->get('exception');
    if ($status && $status->getStatusCode() !== 200) {
      $cacheMetadata->setCacheMaxAge(0);
    }

    $allowed_states = [];
    $route_entity = $this->getEntityFromRouteMatch($this->routeMatch);
    if (is_null($route_entity)) {
      $allowed_states = [
        self::$KINO,
      ];
    }
    elseif ($route_entity instanceof ContentEntityInterface && $route_entity->hasField('field_universe')) {
      $cacheMetadata->addCacheableDependency($route_entity);

      if (!$route_entity->get('field_universe')->isEmpty()) {
        foreach ($route_entity->get('field_universe')
                   ->getValue() as $delta => $values) {
          $allowed_states[] = $values['value'] ?? self::$KINO;
        }
      }
    }
    elseif ($route_entity->getEntityTypeId() == 'node') {
      switch ($route_entity->bundle()) {
        case 'series':
          $allowed_states = [
            self::$STREAMING_GUIDE,
          ];
          break;
        case 'movie':
          if (!empty($route_entity->get('field_streaming_providers')->getValue())) {
            $allowed_states = [
              self::$KINO,
              self::$STREAMING_GUIDE,
            ];
          }
          break;
      }
    }

    // Default to Kino if no states found
    if (empty($allowed_states)) {
      $allowed_states = [
        self::$KINO,
      ];
    }

    $transformation = [
      'type' => 'state',
      'state' => 'universe',
      'allowed_states' => $allowed_states,
    ];
    $cacheMetadata->applyTo($transformation);

    return $transformation;
  }

  /**
   * Returns an entity parameter from a route match object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return mixed|null
   *   The entity, or null if it's not an entity route.
   */
  protected function getEntityFromRouteMatch(RouteMatchInterface $route_match) {
    $route = $route_match->getRouteObject();
    if (!$route) {
      return NULL;
    }

    $entity_type_id = $this->getEntityTypeFromRoute($route);
    if ($entity_type_id) {
      return $route_match->getParameter($entity_type_id);
    }

    return NULL;
  }

  /**
   * Return the entity type id from a route object.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route object.
   *
   * @return string|null
   *   The entity type id, null if it doesn't exist.
   */
  protected function getEntityTypeFromRoute(Route $route): ?string {
    if (!empty($route->getOptions()['parameters'])) {
      foreach ($route->getOptions()['parameters'] as $option) {
        if (isset($option['type']) && strpos($option['type'], 'entity:') === 0) {
          return substr($option['type'], strlen('entity:'));
        }
      }
    }

    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['url.path']);
  }

  /**
   * @inheritDoc
   */
  public function getCacheTags() {
    $tags = parent::getCacheTags();
    $tags[] = 'languages';
    /** @var \Drupal\Core\Entity\ContentEntityInterface $route_entity */
    $route_entity = $this->getEntityFromRouteMatch($this->routeMatch);
    if ($route_entity instanceof ContentEntityInterface) {
      $tags = array_merge($tags, $route_entity->getCacheTagsToInvalidate());
    }
    return $tags;
  }

}
