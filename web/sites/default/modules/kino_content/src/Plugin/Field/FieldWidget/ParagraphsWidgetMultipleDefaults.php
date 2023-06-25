<?php

namespace Drupal\kino_content\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Plugin\Field\FieldWidget\ParagraphsWidget;
use Drupal\paragraphs_asymmetric_translation_widgets\Plugin\Field\FieldWidget\ParagraphsClassicAsymmetricWidget;

/**
 * Plugin implementation of the
 * 'paragraphs_multiple_defaults' widget.
 *
 * @FieldWidget(
 *   id = "paragraphs_multiple_defaults",
 *   label = @Translation("Paragraphs (Multiple Default
 *   Values)"), field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class ParagraphsWidgetMultipleDefaults extends ParagraphsWidget {

  /**
   * Adds multiple items of the default paragraph.
   */
  public function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $this->fieldParents = $form['#parents'];
    $field_state = static::getWidgetState($this->fieldParents, $field_name, $form_state);

    $max = $field_state['items_count'];
    $entity_type_manager = \Drupal::entityTypeManager();

    // Consider adding a default paragraph for new host entities.
    if ($max == 0 && $items->getEntity()->isNew()) {
      $default_type = $this->getDefaultParagraphTypeMachineName();

      // Checking if default_type is not none and if is allowed.
      if ($default_type) {

        // TODO Move $count into settings for the plugin.
        $count = 2;

        for ($i = 0; $i < $count; $i++) {
          // Place a default paragraph.
          $target_type = $this->getFieldSetting('target_type');

          /** @var \Drupal\paragraphs\ParagraphInterface $paragraphs_entity */
          $paragraphs_entity = $entity_type_manager->getStorage($target_type)->create([
            'type' => $default_type,
          ]);
          $paragraphs_entity->setParentEntity($items->getEntity(), $field_name);
          $field_state['selected_bundle'] = $default_type;
          $display = EntityFormDisplay::collectRenderDisplay($paragraphs_entity, $this->getSetting('form_display_mode'));
          $field_state['paragraphs'][$i] = [
            'entity' => $paragraphs_entity,
            'display' => $display,
            'mode' => 'edit',
            'original_delta' => $i + 1,
          ];
        }

        $max = $count;
        $field_state['items_count'] = $max;
      }
    }

    static::setWidgetState($this->fieldParents, $field_name, $form_state, $field_state);
    return parent::formMultipleElements($items, $form, $form_state);
  }

}
