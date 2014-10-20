<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldType\StringLongItem.
 */

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the 'string_long' field type.
 *
 * @FieldType(
 *   id = "string_long",
 *   label = @Translation("Text (plain, long)"),
 *   description = @Translation("A field containing a long string value."),
 *   default_widget = "string_textarea",
 *   default_formatter = "string",
 * )
 */
class StringLongItem extends StringItem {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'text',
          'size' => 'big',
        ),
      ),
    );
  }

}
