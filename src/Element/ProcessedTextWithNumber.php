<?php

namespace Drupal\text_with_number\Element;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\filter\Entity\FilterFormat;
use Drupal\filter\Plugin\FilterInterface;
use Drupal\filter\Render\FilteredMarkup;

/**
 * Provides a processed text with number render element.
 *
 * @RenderElement("processed_text_with_number")
 */
class ProcessedTextWithNumber extends RenderElement {

  /**
   * Wraps a logger channel.
   *
   * @param string $channel
   *   The name of the channel.
   *
   * @return \Psr\Log\LoggerInterface
   *   The logger for this channel.
   */
  protected static function logger($channel) {
    return \Drupal::logger($channel);
  }

  /**
   * Wraps the config factory.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected static function configFactory() {
    return \Drupal::configFactory();
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#theme' => 'text_with_number',
      '#field_definition' => NULL,
      '#view_mode' => '',
      '#text_value' => '',
      '#text_format' => NULL,
      '#text_filter_types_to_skip' => [],
      '#text_markup' => '',
      '#number_original_value' => '',
      '#number_formatted_value' => '',
      '#number_markup' => '',
      '#number_prefix' => '',
      '#number_suffix' => '',
      '#langcode' => '',
      '#pre_render' => [
        [get_class($this), 'preRenderTextWithNumber'],
      ],
    ];
  }

  /**
   * Pre-render callback: Renders a processed text with number element into #markup.
   *
   * Runs all the enabled filters on a piece of text.
   *
   * Note: Because filters can inject JavaScript or execute PHP code, security
   * is vital here. When a user supplies a text format, you should validate it
   * using $format->access() before accepting/using it. This is normally done in
   * the validation stage of the Form API. You should for example never make a
   * preview of content in a disallowed format.
   *
   * @param array $element
   *   A structured array with the following key-value pairs:
   *   - #text_value: containing the text to be filtered.
   *   - #text_format: containing the machine name of the filter format to be used to
   *     filter the text. Defaults to the fallback format.
   *   - #text_filter_types_to_skip: an array of filter types to skip, or an empty
   *     array (default) to skip no filter types. All of the format's filters
   *     will be applied, except for filters of the types that are marked to be
   *     skipped. FilterInterface::TYPE_HTML_RESTRICTOR is the only type that
   *     cannot be skipped.
   *   - #number_original_value: containing the number original value.
   *   - #number_formatted_value: containing the number formatted value.
   *   - #number_markup: containing the number formatted value to be render.
   *   - #number_prefix: containing the number prefix.
   *   - #number_suffix: containing the number suffix.
   *   - #langcode: the language code of the text to be filtered, e.g. 'en' for
   *     English. This allows filters to be language-aware so language-specific
   *     text replacement can be implemented. Defaults to an empty string.
   *
   * @return array
   *   The passed-in element with the filtered text in '#markup'.
   *
   * @ingroup sanitization
   */
  public static function preRenderTextWithNumber($element) {
    $element = self::preRenderText($element);

    $element['#markup'] = t('<p class="text_with_number__text_item">') . $element['#text_markup'] . t('</p><p class="text_with_number__number_item">') . $element['#number_markup'] . t('</p>');

    return $element;
  }

  /**
   * Pre-render callback: Renders a processed text with number element into #markup.
   *
   * Runs all the enabled filters on a piece of text.
   *
   * Note: Because filters can inject JavaScript or execute PHP code, security
   * is vital here. When a user supplies a text format, you should validate it
   * using $format->access() before accepting/using it. This is normally done in
   * the validation stage of the Form API. You should for example never make a
   * preview of content in a disallowed format.
   *
   * @param array $element
   *   A structured array with the following key-value pairs:
   *   - #text_value: containing the text to be filtered.
   *   - #text_format: containing the machine name of the filter format to be used to
   *     filter the text. Defaults to the fallback format.
   *   - #text_filter_types_to_skip: an array of filter types to skip, or an empty
   *     array (default) to skip no filter types. All of the format's filters
   *     will be applied, except for filters of the types that are marked to be
   *     skipped. FilterInterface::TYPE_HTML_RESTRICTOR is the only type that
   *     cannot be skipped.
   *   - #langcode: the language code of the text to be filtered, e.g. 'en' for
   *     English. This allows filters to be language-aware so language-specific
   *     text replacement can be implemented. Defaults to an empty string.
   *
   * @return array
   *   The passed-in element with the filtered text in '#markup'.
   *
   * @ingroup sanitization
   */
  public static function preRenderText($element) {
    $text_format_id = $element['#text_format'];
    $text_filter_types_to_skip = $element['#text_filter_types_to_skip'];
    $text_value = $element['#text_value'];
    $langcode = $element['#langcode'];

    // Convert all Windows and Mac newlines to a single newline, so filters only need to deal with one possibility.
    $text_value = str_replace(["\r\n", "\r"], "\n", $text_value);

    // Fallback text format.
    if (!isset($text_format_id)) {
      $text_filter_settings = self::configFactory()->get('filter.settings');
      $text_format_id = $text_filter_settings->get('fallback_format');
      // Ensure 'filter.settings' config's cacheability is respected.
      CacheableMetadata::createFromRenderArray($element)->addCacheableDependency($text_filter_settings)->applyTo($element);
    }

    // If the requested text format doesn't exist or its disabled, the text cannot be filtered.
    /** @var \Drupal\filter\Entity\FilterFormat $text_format **/
    $text_format = FilterFormat::load($text_format_id);
    if (!$text_format || !$text_format->status()) {
      $message = !$text_format ? 'Missing text format: %format.' : 'Disabled text format: %format.';
      self::logger('filter')->alter($message, ['%format' => $text_format_id]);
      $element['#text_markup'] = '';
      return $element;
    }

    // Get a complete list of filters, ordered properly.
    /** @var \Drupal\filter\Plugin\FilterInterface[] $text_filters **/
    $text_filters = $text_format->filters();

    $text_filter_must_be_applied = function (FilterInterface $filter) use ($text_filter_types_to_skip) {
      $enabled = $filter->status === TRUE;
      $type = $filter->getType();
      // Prevent FilterInterface::TYPE_HTML_RESTRICTOR from being skipped.
      $text_filter_type_must_be_applied = $type == FilterInterface::TYPE_HTML_RESTRICTOR || !in_array($type, $text_filter_types_to_skip);
      return $enabled && $text_filter_type_must_be_applied;
    };

    // Give filters a chance to escape HTML-like data such as code or formulas.
    foreach ($text_filters as $filter) {
      if ($text_filter_must_be_applied($filter)) {
        $text_value = $filter->prepare($text_value, $langcode);
      }
    }

    // Perform filtering.
    $metadata = BubbleableMetadata::createFromRenderArray($element);
    foreach ($text_filters as $filter) {
      if ($text_filter_must_be_applied($filter)) {
        $result = $filter->process($text_value, $langcode);
        $metadata = $metadata->merge($result);
        $text_value = $result->getProcessedText();
      }
    }

    // Filtering and sanitizing have been done in
    // \Drupal\filter\Plugin\FilterInterface. $text is not guaranteed to be
    // safe, but it has been passed through the filter system and checked with
    // a text format, so it must be printed as is. (See the note about security
    // in the method documentation above.)
    $element['#text_markup'] = FilteredMarkup::create($text_value);

    // Set the updated bubbleable rendering metadata and the text format's
    // cache tag.
    $metadata->applyTo($element);
    $element['#cache']['tags'] = Cache::mergeTags($element['#cache']['tags'], $text_format->getCacheTags());

    return $element;
  }

}
