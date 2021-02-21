<?php

namespace Drupal\text_with_number\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'text_default_with_integer' formatter.
 *
 * @FieldFormatter(
 *   id = "text_default_with_integer",
 *   label = @Translation("Text Default with Integer"),
 *   field_types = {
 *     "text_with_integer",
 *   }
 * )
 *
 * @see \Drupal\text\Plugin\Field\FieldFormatter\TextDefaultFormatter
 * @see \Drupal\Core\Field\Plugin\Field\FieldFormatter\IntegerFormatter
 */
class TextDefaultWithIntegerFormatter extends TextWithNumberFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    // The ProcessedTextWithNumber element already handles cache context & tag bubbling.
    // @see \Drupal\text_with_number\Element\ProcessedTextWithNumber::preRenderText()
    foreach ($items as $delta => $item) {
      $elements[$delta] = $elements[$delta] + [
        '#type' => 'processed_text_with_number',
        '#text_value' => $item->text_value,
        '#text_format' => $item->text_format,
        '#langcode' => $item->getLangcode(),
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function numberFormat($number) {
    return number_format($number, 0, '', $this->getSetting('number')['thousand_separator']);
  }
}
