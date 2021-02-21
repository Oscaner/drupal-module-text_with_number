<?php

namespace Drupal\text_with_number\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\AllowedTagsXssTrait;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

abstract class TextWithNumberFormatterBase extends FormatterBase {

  use AllowedTagsXssTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'number' => [
        'thousand_separator' => '',
        'prefix_suffix' => TRUE,
      ],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // Number settings form.
    $elements['number'] = [
      '#type' => 'details',
      '#title' => t('Number settings'),
      '#callapsible' => TRUE,
      '#collapsed' => FALSE,
      '#open' => TRUE,
    ];

    $options = [
      ''  => t('- None -'),
      '.' => t('Decimal point'),
      ',' => t('Comma'),
      ' ' => t('Space'),
      chr(8201) => t('Thin space'),
      "'" => t('Apostrophe'),
    ];

    $elements['number']['thousand_separator'] = [
      '#type' => 'select',
      '#title' => t('Thousand marker'),
      '#options' => $options,
      '#default_value' => $this->getSetting('number')['thousand_separator'],
      '#weight' => 0,
    ];

    $elements['number']['prefix_suffix'] = [
      '#type' => 'checkbox',
      '#title' => t('Display prefix and suffix'),
      '#default_value' => $this->getSetting('number')['prefix_suffix'],
      '#weight' => 10,
    ];

    return $elements + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t('Number: @number', ['@number' => $this->numberFormat(1234.1234567890)]);

    if ($this->getSetting('number')['prefix_suffix']) {
      $summary[] = t('Display number with prefix and suffix.');
    }

    return $summary + parent::settingsSummary();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $field_settings = $this->getFieldSettings();

    foreach ($items as $delta => $item) {
      $number_markup = $this->numberFormat($item->number_value);

      $prefixes = isset($field_settings['number']['prefix']) ? array_map(['Drupal\Core\Field\FieldFilteredMarkup', 'create'], explode('|', $field_settings['number']['prefix'])) : [''];
      $suffixes = isset($field_settings['number']['suffix']) ? array_map(['Drupal\Core\Field\FieldFilteredMarkup', 'create'], explode('|', $field_settings['number']['suffix'])) : [''];
      $prefix = (count($prefixes) > 1) ? $this->formatPlural($item->number_value, $prefixes[0], $prefixes[1]) : $prefixes[0];
      $suffix = (count($suffixes) > 1) ? $this->formatPlural($item->number_value, $suffixes[0], $suffixes[1]) : $suffixes[0];

      // Account for prefix and suffix.
      if ($this->getSetting('number')['prefix_suffix']) {
        $number_markup = $prefix . $number_markup . $suffix;
      }

      // Output the raw value in a content attribute if the text of the HTML
      // element differs from the raw value (for example when a prefix is used).
      if (isset($item->_attributes) && $item->number_value != $number_markup) {
        $item->_attributes += ['content' => $item->number_value];
      }

      $elements[$delta] = [
        '#field_definition' => $this->fieldDefinition,
        '#view_mode' => $this->viewMode,
        '#number_original_value' => $item->number_value,
        '#number_formatted_value' => $this->numberFormat($item->number_value),
        '#number_markup' => $number_markup,
        '#number_prefix' => $prefix,
        '#number_suffix' => $suffix,
      ];
    }

    return $elements;
  }

  /**
   * Formats a number.
   *
   * @param mixed $number
   *   The numeric value.
   *
   * @return string
   *   The formatted number.
   */
  abstract protected function numberFormat($number);

}
