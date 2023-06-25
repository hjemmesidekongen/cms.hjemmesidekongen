<?php

namespace Drupal\kino_content\Plugin\Transform\Field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\media\Entity\MediaType;
use Drupal\media\Plugin\media\Source\OEmbedInterface;
use Drupal\transform_api\Plugin\Transform\Field\EntityReferenceLinksTransform;
use Drupal\transform_api\Transform\EntityTransform;

/**
 * @FieldTransform(
 *  id = "entity_reference_person",
 *  label = @Translation("Entity reference person"),
 *  field_types = {
 *    "entity_reference"
 *  }
 * )
 */
class EntityReferencePersonTransform extends EntityReferenceLinksTransform {

  public function transformElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::transformElements($items, $langcode);
    $metadata = CacheableMetadata::createFromRenderArray($elements);
    $mediaViewBuilder = $this->entityTypeManager->getViewBuilder('media');
    foreach ($elements as $delta => $element) {
      if (!isset($element['id'])) {
        continue;
      }
      $node = $this->entityTypeManager->getStorage('node')->load($element['id']);

      $elements[$delta]['image'] = NULL;
      /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $image */
      $image = $node->get('field_image');
      foreach ($image->referencedEntities() as $media) {
        $elements[$delta]['image'] = new EntityTransform('media', $media->id(), 'profile_small_circle');
        $metadata->addCacheableDependency($media);
      }
    }
    $metadata->applyTo($elements);
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    if ($field_definition->getTargetEntityTypeId() !== 'paragraph') {
      return FALSE;
    }

    if (parent::isApplicable($field_definition)) {
      $node_type = $field_definition->getTargetBundle();

      if ($node_type == 'cast_member') {
        return TRUE;
      }
    }
    return FALSE;
  }

}
