<?php

namespace Drupal\text_with_number\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'text_textfield_with_number' widget.
 *
 * @FieldWidget(
 *   id = "text_textfield_with_number",
 *   label = @Translation("Text field with Number"),
 *   field_types = {
 *     "text_with_integer"
 *   },
 * )
 *
 * @see \Drupal\text\Plugin\Field\FieldWidget\TextfieldWidget
 * @see \Drupal\Core\Field\Plugin\Field\FieldWidget\NumberWidget
 */
class TextfieldWithNumberWidget extends TextWithNumberWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $settings['text']['size'] = 60;
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    // Text size.
    $element['text']['size'] = [
      '#type' => 'number',
      '#title' => t('Size of textfield'),
      '#default_value' => $this->getSetting('text')['size'],
      '#required' => TRUE,
      '#min' => 1,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t('<ul>');

    // Text size.
    $summary[] = t('Text size: @size', ['@size' => $this->getSetting('text')['size']]);

    // Text placeholder.
    $text_placeholder = $this->getSetting('text')['placeholder'];
    if (!empty($text_placeholder)) {
      $summary[] = t('Placeholder: @placeholder', ['@placeholder' => $text_placeholder]);
    }

    // Text help link.
    $text_hide_help_link = $this->getSetting('text')['hide_help'];
    if ($text_hide_help_link) {
      $summary[] = t('Hide the help link <em>About text formats</em>.');
    }

    // Text format guidelines.
    $text_hide_format_guidelines = $this->getSetting('text')['hide_guidelines'];
    if ($text_hide_format_guidelines) {
      $summary[] = t('Hide text format guidelines.');
    }

    $summary[] = t('</ul><ul>');

    // Number placeholder.
    $number_placeholder = $this->getSetting('number')['placeholder'];
    if (!empty($number_placeholder)) {
      $summary[] = t('Placeholder: @placeholder', ['@placeholder' => $number_placeholder]);
    }

    $summary[] = t('</ul>');

    return $summary + parent::settingsSummary();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $storage_settings = $this->fieldDefinition->getFieldStorageDefinition()->getSettings();
    $field_settings = $this->getFieldSettings();
    $widget_settings = $this->getSettings();

    $element['text_value'] = [
      '#type' => 'text_format',
      '#format' => isset($items[$delta]->text_format) ? $items[$delta]->text_format : NULL,
      '#base_type' => 'textfield',
      '#default_value' => isset($items[$delta]->text_value) ? $items[$delta]->text_value : NULL,
      '#size' => $widget_settings['text']['size'],
      '#placeholder' => $widget_settings['text']['placeholder'],
      '#maxlength' => $storage_settings['text']['max_length'],
      '#attributes' => ['class' => ['js-text-full', 'text-full']],
      '#allowed_format_hide_settings' => [
        'hide_help' => $widget_settings['text']['hide_help'],
        'hide_guidelines' => $widget_settings['text']['hide_guidelines'],
      ]
    ];

    if ($allowed_formats = array_filter($field_settings['text']['allowed_formats'] ?? [])) {
      $element['text_value']['#allowed_formats'] = $allowed_formats;
    }

    return $element + parent::formElement($items, $delta, $element, $form, $form_state);
  }

}
