<?php

namespace Drupal\text_with_number\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Class TextWithNumberItemBase.
 *
 * @package Drupal\text_with_number\Plugin\Field\FieldType
 *
 * @see \Drupal\Core\Field\Plugin\Field\FieldType\NumericItemBase
 * @see \Drupal\text\Plugin\Field\FieldType\TextItemBase
 */
abstract class TextWithNumberItemBase extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Text properties.
    $properties['text_value'] = DataDefinition::create('string')
      ->setLabel(t('Text'))
      ->setRequired(TRUE);

    $properties['text_format'] = DataDefinition::create('filter_format')
      ->setLabel(t('Text format'));

    $properties['text_processed'] = DataDefinition::create('string')
      ->setLabel(t('Processed text'))
      ->setDescription(t('The text with the text format applied.'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\text\TextProcessed')
      ->setSetting('text source', 'text_value')
      ->setInternal(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'text' => [
        'allowed_formats' => [],
      ],
      'number' => [
        'min' => '',
        'max' => '',
        'prefix' => '',
        'suffix' => '',
      ],
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $settings = $this->getSettings();

    // Text settings form.
    $element['text'] = [
      '#type' => 'details',
      '#title' => t('Text settings'),
      '#callapsible' => TRUE,
      '#collapsed' => FALSE,
      '#open' => TRUE,
    ];

    $options = [];
    foreach (filter_formats() as $format) {
      $options[$format->id()] = $format->label();
    }

    $element['text']['allowed_formats'] = [
      '#type' => 'checkboxes',
      '#title' => t('Allowed formats'),
      '#options' => $options,
      '#default_value' => $settings['text']['allowed_formats'] ?? [],
      '#description' => t('Restrict which text formats are allowed, given the user has the required permissions. If no text formats are selected, then all the ones the user has access to will be available.'),
    ];

    // Number settings form.
    $element['number'] = [
      '#type' => 'details',
      '#title' => t('Number settings'),
      '#callapsible' => TRUE,
      '#collapsed' => FALSE,
      '#open' => TRUE,
    ];

    $element['number']['min'] = [
      '#type' => 'number',
      '#title' => t('Minimum'),
      '#default_value' => $settings['number']['min'] ?? NULL,
      '#description' => t('The minimum value that should be allowed in this field. Leave blank for no minimum.'),
    ];

    $element['number']['max'] = [
      '#type' => 'number',
      '#title' => t('Maximum'),
      '#default_value' => $settings['number']['max'] ?? NULL,
      '#description' => t('The maximum value that should be allowed in this field. Leave blank for no maximum.'),
    ];

    $element['number']['prefix'] = [
      '#type' => 'textfield',
      '#title' => t('Prefix'),
      '#default_value' => $settings['number']['prefix'] ?? NULL,
      '#size' => 60,
      '#description' => t("Define a string that should be prefixed to the value, like '$ ' or '&euro; '. Leave blank for none. Separate singular and plural values with a pipe ('pound|pounds')."),
    ];

    $element['number']['suffix'] = [
      '#type' => 'textfield',
      '#title' => t('Suffix'),
      '#default_value' => $settings['number']['suffix'] ?? NULL,
      '#size' => 60,
      '#description' => t("Define a string that should be suffixed to the value, like ' m', ' kb/s'. Leave blank for none. Separate singular and plural values with a pipe ('pound|pounds')."),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    // @todo: Add in the text filter default format here.
    $this->setValue(['text_format' => NULL], $notify);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $text_value = $this->get('text_value')->getValue();
    $text_is_empty = $text_value === NULL || $text_value === '';

    $number_value = $this->get('number_value')->getValue();
    $number_is_empty = empty($number_value) || (string) $number_value === '0';

    return $text_is_empty && $number_is_empty;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints = parent::getConstraints();

    $settings = $this->getSettings();
    $label = $this->getFieldDefinition()->getLabel();

    // Number min validate.
    if (isset($settings['number']['min']) && $settings['number']['min'] !== '') {
      $number_min = $settings['number']['min'];
      $constraints[] = $constraint_manager->create('ComplexData', [
        'number_value' => [
          'Range' => [
            'min' => $number_min,
            'minMessage' => t('%name: the number value may be no less than %min.', ['%name' => $label, '%min' => $number_min]),
          ],
        ],
      ]);
    }

    // Number max validate.
    if (isset($settings['number']['max']) && $settings['number']['max'] !== '') {
      $number_max = $settings['number']['max'];
      $constraints[] = $constraint_manager->create('ComplexData', [
        'number_value' => [
          'Range' => [
            'max' => $number_max,
            'maxMessage' => t('%name: the number value may be no greater than %max.', ['%name' => $label, '%max' => $number_max]),
          ],
        ],
      ]);
    }

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($property_name, $notify = TRUE) {
    // Unset processed properties that are affected by the change.
    foreach ($this->definition->getPropertyDefinitions() as $property => $definition) {
      if ($definition->getClass() == '\Drupal\text\TextProcessed') {
        if ($property_name == 'text_format' || ($definition->getSetting('text source') == $property_name)) {
          $this->writePropertyValue($property, NULL);
        }
      }
    }
    parent::onChange($property_name, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $settings = $field_definition->getSettings();

    if (empty($settings['text']['max_length'])) {
      // Textarea handling.
      $text_value = $random->paragraphs();
    }
    else {
      // Textfield handling.
      $text_value = substr($random->sentences(mt_rand(1, $settings['text']['max_length'] / 3), FALSE), 0, $settings['text']['max_length']);
    }

    $values = [
      'text_value' => $text_value,
      'text_summary' => $text_value,
      'text_format' => filter_fallback_format(),
    ];
    return $values;
  }

  /**
   * Helper method to truncate a decimal number to a given number of decimals.
   *
   * @param float $decimal
   *   Decimal number to truncate.
   * @param int $num
   *   Number of digits the output will have.
   *
   * @return float
   *   Decimal number truncated.
   */
  protected static function truncateDecimal($decimal, $num) {
    return floor($decimal * pow(10, $num)) / pow(10, $num);
  }

}
