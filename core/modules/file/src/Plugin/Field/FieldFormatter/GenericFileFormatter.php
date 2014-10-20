<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\field\formatter\GenericFileFormatter.
 */

namespace Drupal\file\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'file_default' formatter.
 *
 * @FieldFormatter(
 *   id = "file_default",
 *   label = @Translation("Generic file"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class GenericFileFormatter extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      if ($item->isDisplayed() && $item->entity) {
        $elements[$delta] = array(
          '#theme' => 'file_link',
          '#file' => $item->entity,
          '#description' => $item->description,
        );
        // Pass field item attributes to the theme function.
        if (isset($item->_attributes)) {
          $elements[$delta] += array('#attributes' => array());
          $elements[$delta]['#attributes'] += $item->_attributes;
          // Unset field item attributes since they have been included in the
          // formatter output and should not be rendered in the field template.
          unset($item->_attributes);
        }
      }
    }

    return $elements;
  }

}
