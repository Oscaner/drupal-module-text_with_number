<?php

namespace Drupal\text_with_number\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'text with integer' field type.
 *
 * @FieldType(
 *   id = "text_with_integer",
 *   label = @Translation("Text with Number (formatted with Integer)"),
 *   description = @Translation("This field stores a text with number, text with a text format and number as an integer."),
 *   category = @Translation("Text with Number"),
 *   default_widget = "text_textfield_with_number",
 *   default_formatter = "text_default_with_integer"
 * )
 *
 * @see \Drupal\text\Plugin\Field\FieldType\TextItem
 * @see \Drupal\Core\Field\Plugin\Field\FieldType\IntegerItem
 */
class TextWithIntegerItem extends TextWithNumberItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'text' => [
        'max_length' => 255,
      ],
      'number' => [
        'unsigned' => FALSE,
        // Valid size property values include: 'tiny', 'small', 'medium', 'normal'
        // and 'big'.
        'size' => 'normal',
      ],
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['number_value'] = DataDefinition::create('integer')
      ->setLabel(t('Integer value'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'text_value' => [
          'type' => 'varchar',
          'length' => $field_definition->getSetting('text')['max_length'],
        ],
        'text_format' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'number_value' => [
          'type' => 'int',
          // Expose the 'unsigned' setting in the field item schema.
          'unsigned' => $field_definition->getSetting('number')['unsigned'],
          // Expose the 'size' setting in the field item schema. For instance,
          // supply 'big' as a value to produce a 'bigint' type.
          'size' => $field_definition->getSetting('number')['size'],
        ],
      ],
      'indexes' => [
        'text_format' => ['text_format'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints = parent::getConstraints();

    $storage_settings = $this->getFieldDefinition()->getFieldStorageDefinition()->getSettings();
    $settings = $this->getSettings();
    $label = $this->getFieldDefinition()->getLabel();

    // Text max length validate.
    if (isset($storage_settings['text']['max_length']) && $storage_settings['text']['max_length'] !== '') {
      $text_max_length = $storage_settings['text']['max_length'];
      $constraints[] = $constraint_manager->create('ComplexData', [
        'text_value' => [
          'Length' => [
            'max' => $text_max_length,
            'maxMessage' => t('%name: The text value may not be longer than @max characters.', ['%name' => $label, '@max' => $text_max_length]),
          ],
        ],
      ]);
    }

    // Number unsigned validate.
    if (isset($settings['number']['unsigned']) && $settings['number']['unsigned']) {
      $constraints[] = $constraint_manager->create('ComplexData', [
        'number_value' => [
          'Range' => [
            'min' => 0,
            'minMessage' => t('%name: The integer value must be larger or equal to %min.', ['%name' => $label, '%min' => 0]),
          ],
        ],
      ]);
    }

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $storage_settings = $this->getFieldDefinition()->getFieldStorageDefinition()->getSettings();

    // Text settings form.
    $element['text'] = [
      '#type' => 'details',
      '#title' => t('Text settings'),
      '#callapsible' => TRUE,
      '#collapsed' => FALSE,
      '#open' => TRUE,
    ];

    $element['text']['max_length'] = [
      '#type' => 'number',
      '#title' => t('Maximum length'),
      '#default_value' => $storage_settings['text']['max_length'],
      '#required' => TRUE,
      '#description' => t('The maximum length of the field in characters.'),
      '#min' => 1,
      '#disabled' => $has_data,
    ];

    return $element + parent::storageSettingsForm($form, $form_state, $has_data);
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values = parent::generateSampleValue($field_definition);

    $number_min = $field_definition->getSetting('number')['min'] ?: 0;
    $number_max = $field_definition->getSetting('number')['max'] ?: 999;
    $values['number_value'] = mt_rand($number_min, $number_max);

    return $values;
  }

}
