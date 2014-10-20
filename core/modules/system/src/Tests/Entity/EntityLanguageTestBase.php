<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Entity\EntityLanguageTestBase.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\field\Entity\FieldConfig;

/**
 * Base class for language-aware entity tests.
 */
abstract class EntityLanguageTestBase extends EntityUnitTestBase {

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The available language codes.
   *
   * @var array
   */
  protected $langcodes;

  /**
   * The test field name.
   *
   * @var string
   */
  protected $field_name;

  /**
   * The untranslatable test field name.
   *
   * @var string
   */
  protected $untranslatable_field_name;

  public static $modules = array('language', 'entity_test');

  protected function setUp() {
    parent::setUp();

    $this->languageManager = $this->container->get('language_manager');

    $this->installEntitySchema('entity_test_rev');
    $this->installEntitySchema('entity_test_mul');
    $this->installEntitySchema('entity_test_mulrev');

    $this->installConfig(array('language'));

    // Create the test field.
    entity_test_install();

    // Enable translations for the test entity type.
    $this->state->set('entity_test.translation', TRUE);

    // Create a translatable test field.
    $this->field_name = drupal_strtolower($this->randomMachineName() . '_field_name');

    // Create an untranslatable test field.
    $this->untranslatable_field_name = drupal_strtolower($this->randomMachineName() . '_field_name');

    // Create field fields in all entity variations.
    foreach (entity_test_entity_types() as $entity_type) {
      entity_create('field_storage_config', array(
        'field_name' => $this->field_name,
        'entity_type' => $entity_type,
        'type' => 'text',
        'cardinality' => 4,
      ))->save();
      entity_create('field_config', array(
        'field_name' => $this->field_name,
        'entity_type' => $entity_type,
        'bundle' => $entity_type,
        'translatable' => TRUE,
      ))->save();
      $this->field[$entity_type] = entity_load('field_config', $entity_type . '.' . $entity_type . '.' . $this->field_name);

      entity_create('field_storage_config', array(
        'field_name' => $this->untranslatable_field_name,
        'entity_type' => $entity_type,
        'type' => 'text',
        'cardinality' => 4,
      ))->save();
      entity_create('field_config', array(
        'field_name' => $this->untranslatable_field_name,
        'entity_type' => $entity_type,
        'bundle' => $entity_type,
        'translatable' => FALSE,
      ))->save();
    }

    // Create the default languages.
    $this->installConfig(array('language'));

    // Create test languages.
    $this->langcodes = array();
    for ($i = 0; $i < 3; ++$i) {
      $language = ConfigurableLanguage::create(array(
        'id' => 'l' . $i,
        'label' => $this->randomString(),
        'weight' => $i,
      ));
      $this->langcodes[$i] = $language->id();
      $language->save();
    }
  }

  /**
   * Toggles field storage translatability.
   *
   * @param string $entity_type
   *   The type of the entity fields are attached to.
   */
  protected function toggleFieldTranslatability($entity_type, $bundle) {
    $fields = array($this->field_name, $this->untranslatable_field_name);
    foreach ($fields as $field_name) {
      $field = FieldConfig::loadByName($entity_type, $bundle, $field_name);
      $translatable = !$field->isTranslatable();
      $field->set('translatable', $translatable);
      $field->save();
      $field = FieldConfig::loadByName($entity_type, $bundle, $field_name);
      $this->assertEqual($field->isTranslatable(), $translatable, 'Field translatability changed.');
    }
    \Drupal::cache('entity')->deleteAll();
  }

}
