<?php

namespace Drupal\kino_content\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsButtonsWidget;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'key_value' widget.
 *
 * @FieldWidget(
 *   id = "dynamic_key_value_radios_widget",
 *   label = @Translation("Dynamic Key value radio list"),
 *   field_types = {
 *     "dynamic_key_value"
 *   },
 *   multiple_values = TRUE
 * )
 */
class DynamicKeyValueRadiosWidget extends OptionsWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $options = $this->getOptions($items->getEntity());
    $selected = $this->getSelectedOptions($items);

    // If required and there is one single option, preselect it.
    if ($this->required && count($options) == 1) {
      reset($options);
      $selected = [key($options)];
    }

    $element += [
      '#type' => 'radios',
      // Radio buttons need a scalar value. Take the first default value, or
      // default to NULL so that the form element is properly recognized as
      // not having a default value.
      '#default_value' => $selected ? reset($selected) : NULL,
      '#options' => $options,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEmptyLabel() {
    if (!$this->required && !$this->multiple) {
      return $this->t('N/A');
    }
  }
}
