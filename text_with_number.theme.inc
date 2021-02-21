<?php

/**
 * @file
 * Primary theme hooks for Text with number module.
 */

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Template\Attribute;

/**
 * Prepares variables for text with number element templates.
 *
 * Default template: text-with-number.html.twig
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the properties of the elements.
 *     Properties used: #attributes, #content, #text, #number.
 */
function template_preprocess_text_with_number(array &$variables) {
  $variables['text'] = $variables['elements']['#text_markup'] ?? NULL;
  $variables['number'] = $variables['elements']['#number_markup'] ?? NULL;

  $attributes = new Attribute($variables['attributes'] ?? []);
  $attributes->addClass('text-with-number--item');
  $variables['attributes'] = $attributes->toArray();

  $variables['content']['text'] = [
    '#theme' => 'text_with_number__item',
    '#elements' => [
      '#field_definition' => $variables['elements']['#field_definition'] ?? NULL,
      '#view_mode' => $variables['elements']['#view_mode'],
    ],
    '#value_type' => 'text',
    '#value' => $variables['text'],
  ];

  $variables['content']['number'] = [
    '#theme' => 'text_with_number__item',
    '#elements' => [
      '#field_definition' => $variables['elements']['#field_definition'] ?? NULL,
      '#view_mode' => $variables['elements']['#view_mode'],
    ],
    '#value_type' => 'number',
    '#value' => $variables['number'],
  ];
}

/**
 * Prepares variables for text with number item element templates.
 *
 * Default template: text-with-number--item.html.twig
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the properties of the elements.
 *     Properties used: #attributes, #value_type, #value.
 */
function template_preprocess_text_with_number__item(array &$variables) {
  $attributes = new Attribute($variables['attributes'] ?? []);
  $attributes->addClass("text-with-number--{$variables['value_type']}-item");
  $variables['attributes'] = $attributes->toArray();
}

/**
 * Handle theme suggestions for text with number theme.
 *
 * @param array $suggestions
 *   The suggestions.
 * @param array $variables
 *   The variables.
 */
function _handle_text_with_number_theme_suggestions(array &$suggestions, array $variables) {
  $field_definition = $variables['elements']['#field_definition'] ?? NULL;
  $entity_type = $field_definition instanceof FieldDefinitionInterface ? $field_definition->getTargetEntityTypeId() : NULL;
  $entity_bundle = $field_definition instanceof FieldDefinitionInterface ? $field_definition->getTargetBundle() : NULL;
  $field_name = $field_definition instanceof FieldDefinitionInterface ? $field_definition->getName() : NULL;
  $view_mode = $variables['elements']['#view_mode'] ?? NULL;

  $prefixes = array_filter([($variables['theme_hook_original'] ?? '')]);

  array_map(function ($prefix) use (&$suggestions, $entity_type, $entity_bundle, $field_name, $view_mode) {
    // Entity type.
    if ($entity_type) {
      $suggestion = [$prefix, $entity_type];
      array_push($suggestions, implode('__', $suggestion));
    }
    // Entity type + field name.
    if ($entity_type && $field_name) {
      $suggestion = [$prefix, $entity_type, $field_name];
      array_push($suggestions, implode('__', $suggestion));
    }
    // Entity type + field name + view mode.
    if ($entity_type && $field_name) {
      $suggestion = [$prefix, $entity_type, $field_name, $view_mode];
      array_push($suggestions, implode('__', $suggestion));
    }
    // Entity type + entity bundle.
    if ($entity_type && $entity_bundle) {
      $suggestion = [$prefix, $entity_type, $entity_bundle];
      array_push($suggestions, implode('__', $suggestion));
    }
    // Entity type + entity bundle + view mode.
    if ($entity_type && $entity_bundle && $view_mode) {
      $suggestion = [$prefix, $entity_type, $entity_bundle, $view_mode];
      array_push($suggestions, implode('__', $suggestion));
    }
    // Entity type + entity bundle + field name.
    if ($entity_type && $entity_bundle && $field_name) {
      $suggestion = [$prefix, $entity_type, $entity_bundle, $field_name];
      array_push($suggestions, implode('__', $suggestion));
    }
    // Entity type + entity bundle + field name + view mode.
    if ($entity_type && $entity_bundle && $field_name && $view_mode) {
      $suggestion = [$prefix, $entity_type, $entity_bundle, $field_name, $view_mode];
      array_push($suggestions, implode('__', $suggestion));
    }
  }, $prefixes);
}
