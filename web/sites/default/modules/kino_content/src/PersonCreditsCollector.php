<?php

namespace Drupal\kino_content;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\transform_api\Transform\EntityTransform;

class PersonCreditsCollector {

  protected EntityTypeManagerInterface $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  public function collectCredits(Node $node): array {
    $cacheableMetadata = new CacheableMetadata();
    $cacheableMetadata->addCacheableDependency($node);
    $paragraphStorage = $this->entityTypeManager->getStorage('paragraph');
    $pids = $paragraphStorage->getQuery()
      ->condition('type', 'cast_member')
      ->condition('field_person', $node->id(), 'IN')
      ->accessCheck()
      ->execute();
    $paragraphs = $paragraphStorage->loadMultiple($pids);
    $movie_roles = [];
    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
    foreach ($paragraphs as $paragraph) {
      $movie = $paragraph->getParentEntity();
      if (empty($movie)) {
        continue;
      }
      $cacheableMetadata->addCacheableDependency($paragraph);
      $cacheableMetadata->addCacheableDependency($movie);
      /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $movie_role */
      $movie_role = $paragraph->get('field_movie_role');
      /** @var \Drupal\taxonomy\Entity\Term $term */
      foreach ($movie_role->referencedEntities() as $term) {
        $term_key = $term->getWeight() . '_' . $term->id();
        if (empty($movie_roles[$term_key])) {
          $cacheableMetadata->addCacheableDependency($term);
          $movie_roles[$term_key] = [
            "type" => "entity",
            "entity_type" => "paragraph",
            "bundle" => "credit",
            "id" => $paragraph->id(),
            "label" => $node->label() . ' > ' . $term->label(),
            "transform_mode" => "default",
            "langcode" => $node->language()->getId(),
            "field_movie_role" => [
              "id" => $term->id(),
              "label" => $term->label(),
              "url" => $term->toUrl()->toString()
            ],
            "field_movies" => [
            ]
          ];
        }
      }
      $movie_entry = [
        "id" => $movie->id(),
        "label" => $movie->label(),
        "url" => $movie->toUrl()->toString(),
        "image" => [],
        "year" => ''
      ];
      if (!$movie->get('field_poster')->isEmpty() && !empty($movie->get('field_poster')->getValue())) {
        $movie_entry["image"] = new EntityTransform('media', $movie->get('field_poster')->first()->getValue()['target_id'], 'profile_small_circle');
      }
      if (!$movie->get('field_premiere')->isEmpty() && !empty($movie->get('field_premiere')->getValue())) {
        $movie_entry["year"] = date('Y', strtotime($movie->get('field_premiere')
          ->first()
          ->getValue()['value']));
      }
      $movie_roles[$term_key]['field_movies'][$movie_entry["year"] . '_' . $movie_entry["label"]] = $movie_entry;
    }

    $roles = [];
    ksort($movie_roles);
    foreach ($movie_roles as $movie_role) {
      $item = $movie_role;
      $item['field_movies'] = [];
      ksort($movie_role['field_movies']);
      foreach ($movie_role['field_movies'] as $movie_item) {
        $item['field_movies'][] = $movie_item;
      }
      $roles[] = $item;
    }
    $cacheableMetadata->applyTo($roles);

    return $roles;
  }

}
