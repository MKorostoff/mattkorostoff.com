<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateNodeTestBase.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;
use Drupal\migrate\Entity\MigrationInterface;

/**
 * Base class for Node migration tests.
 */
abstract class MigrateNodeTestBase extends MigrateDrupalTestBase {

  static $modules = array('node');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    entity_create('node_type', array('type' => 'test_planet'))->save();
    $node_type = entity_create('node_type', array('type' => 'story'));
    $node_type->save();
    node_add_body_field($node_type);

    $id_mappings = array(
      'd6_node_type' => array(
        array(array('test_story'), array('story')),
      ),
      'd6_filter_format' => array(
        array(array(1), array('filtered_html')),
        array(array(2), array('full_html')),
      ),
      'd6_field_instance_widget_settings' => array(
        array(
          array('page', 'field_test'),
          array('node', 'page', 'default', 'test'),
        ),
      ),
      'd6_field_formatter_settings' => array(
        array(
          array('page', 'default', 'node', 'field_test'),
          array('node', 'page', 'default', 'field_test'),
        ),
      ),
    );
    $this->prepareMigrations($id_mappings);

    $migration = entity_load('migration', 'd6_node_settings');
    $migration->setMigrationResult(MigrationInterface::RESULT_COMPLETED);

    // Create a test node.
    $node = entity_create('node', array(
      'type' => 'story',
      'nid' => 1,
      'vid' => 1,
    ));
    $node->enforceIsNew();
    $node->save();

    $node = entity_create('node', array(
      'type' => 'test_planet',
      'nid' => 3,
      'vid' => 4,
    ));
    $node->enforceIsNew();
    $node->save();

    // Load dumps.
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6Node.php',
      $this->getDumpDirectory() . '/Drupal6NodeType.php',
      $this->getDumpDirectory() . '/Drupal6FieldInstance.php',
    );
    $this->loadDumps($dumps);
  }

}
