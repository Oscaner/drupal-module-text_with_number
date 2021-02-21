<?php

namespace Drupal\text_with_number\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Class TextWithNumberWidgetBase.
 *
 * @package Drupal\text_with_number\Plugin\Field\FieldWidget
 */
abstract class TextWithNumberWidgetBase extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'text' => [
        'placeholder' => '',
        'hide_help' => FALSE,
        'hide_guidelines' => FALSE,
      ],
      'number' => [
        'placeholder' => '',
      ],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // Text settings form.
    $element['text'] = [
      '#type' => 'details',
      '#title' => t('Text settings'),
      '#callapsible' => TRUE,
      '#collapsed' => FALSE,
      '#open' => TRUE,
    ];

    $element['text']['placeholder'] = [
      '#type' => 'textfield',
      '#title' => t('Placeholder of textfield'),
      '#default_value' => $this->getSetting('text')['placeholder'],
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];

    $element['text']['hide_help'] = [
      '#type' => 'checkbox',
      '#title' => t('Hide the help link <em>About text formats</em>.'),
      '#default_value' => $this->getSetting('text')['hide_help'],
    ];

    $element['text']['hide_guidelines'] = [
      '#type' => 'checkbox',
      '#title' => t('Hide text format guidelines.'),
      '#default_value' => $this->getSetting('text')['hide_guidelines'],
    ];

    // Number settings form.
    $element['number'] = [
      '#type' => 'details',
      '#title' => t('Number settings'),
      '#callapsible' => TRUE,
      '#collapsed' => FALSE,
      '#open' => TRUE,
    ];

    $element['number']['placeholder'] = [
      '#type' => 'textfield',
      '#title' => t('Placeholder of number'),
      '#default_value' => $this->getSetting('number')['placeholder'],
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];

    return $element + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['#type'] = 'fieldset';

    $field_settings = $this->getFieldSettings();
    $widget_settings = $this->getSettings();

    // Number value field.
    $element['number_value'] = [
      '#type' => 'number',
      '#default_value' => isset($items[$delta]->number_value) ? $items[$delta]->number_value : NULL,
      '#placeholder' => $widget_settings['number']['placeholder'],
    ];

    // Set the step for floating point and decimal numbers.
    switch ($this->fieldDefinition->getType()) {
      case 'decimal':
        $element['number_value']['#step'] = pow(0.1, $field_settings['number']['scale']);
        break;

      case 'float':
        $element['number_value']['#step'] = 'any';
        break;
    }

    // Set minimum and maximum.
    if (is_numeric($field_settings['number']['min'])) {
      $element['number_value']['#min'] = $field_settings['number']['min'];
    }
    if (is_numeric($field_settings['number']['max'])) {
      $element['number_value']['#max'] = $field_settings['number']['max'];
    }

    // Add prefix and suffix.
    if ($field_settings['number']['prefix']) {
      $prefixes = explode('|', $field_settings['number']['prefix']);
      $element['number_value']['#field_prefix'] = FieldFilteredMarkup::create(array_pop($prefixes));
    }
    if ($field_settings['number']['suffix']) {
      $suffixes = explode('|', $field_settings['number']['suffix']);
      $element['number_value']['#field_suffix'] = FieldFilteredMarkup::create(array_pop($suffixes));
    }

    // Element validate.
    $element['#element_validate'] = [[get_class($this), 'validateElement']];

    // After build.
    $element['#after_build'][] = [get_class($this), 'allowedFormatsRemoveTextareaHelp'];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, FormStateInterface $form_state) {
    if ($violation->arrayPropertyPath == ['text_value', 'format'] && isset($element['text_value']['format']['#access']) && !$element['text_value']['format']['#access']) {
      // Ignore validation errors for formats that may not be changed,
      // such as when existing formats become invalid.
      // See \Drupal\filter\Element\TextFormat::processFormat().
      return FALSE;
    }
    return $element;
  }

  /**
   * Form validation handler for widget elements.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    if (isset($element['text_value'])) {
      $text_format_element = $text_value_element = $element['text_value'];
      $text_format_element['#parents'][count($text_format_element['#parents']) - 1] = 'text_format';

      $text_value = $form_state->getValue($text_value_element['#parents'])['value'];
      $text_format = $form_state->getValue($text_value_element['#parents'])['format'];

      $form_state->setValueForElement($text_format_element, $text_format);
      $form_state->setValueForElement($text_value_element, $text_value);
    }
  }

  /**
   * Allowed formats remove textarea help.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form element.
   *
   * @see _allowed_formats_remove_textarea_help()
   */
  public static function allowedFormatsRemoveTextareaHelp(array $element, FormStateInterface $form_state) {
    if (isset($element['text_value']['format'])) {
      if ($element['text_value']['#allowed_format_hide_settings']['hide_help']) {
        unset($element['text_value']['format']['help']);
      }
      if ($element['text_value']['#allowed_format_hide_settings']['hide_guidelines']) {
        unset($element['text_value']['format']['guidelines']);
      }

      // If nothing is left in the wrapper, hide it as well.
      if (
        isset($element['text_value']['#allowed_formats']) &&
        count($element['text_value']['#allowed_formats']) == 1 &&
        $element['text_value']['#allowed_format_hide_settings']['hide_help'] &&
        $element['text_value']['#allowed_format_hide_settings']['hide_guidelines']
      ) {
        unset($element['text_value']['format']['#type']);
        unset($element['text_value']['format']['#theme_wrappers']);
      }
    }

    return $element;
  }

}
