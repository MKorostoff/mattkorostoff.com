<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldType\FloatItem.
 */

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'float' field type.
 *
 * @FieldType(
 *   id = "float",
 *   label = @Translation("Number (float)"),
 *   description = @Translation("This field stores a number in the database in a floating point format."),
 *   default_widget = "number",
 *   default_formatter = "number_decimal"
 * )
 */
class FloatItem extends NumericItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('float')
      ->setLabel(t('Float value'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'float',
          'not null' => FALSE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $settings = $field_definition->getSettings();
    $precision = rand(10, 32);
    $scale = rand(0, 2);
    $max = is_numeric($settings['max']) ?: pow(10, ($precision - $scale)) - 1;
    $min = is_numeric($settings['min']) ?: -pow(10, ($precision - $scale)) + 1;
    // @see "Example #1 Calculate a random floating-point number" in
    // http://php.net/manual/en/function.mt-getrandmax.php
    $random_decimal = $min + mt_rand() / mt_getrandmax() * ($max - $min);
    $values['value'] = self::truncateDecimal($random_decimal, $scale);
    return $values;
  }

}
