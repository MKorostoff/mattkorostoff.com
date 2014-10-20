<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateUploadInstanceTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Uploads migration.
 *
 * @group migrate_drupal
 */
class MigrateUploadFieldTest extends MigrateDrupalTestBase {

  /**
   * The modules to be enabled during the test.
   *
   * @var array
   */
  static $modules = array('file', 'node');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_upload_field');
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6UploadField.php',
    );
    $this->prepare($migration, $dumps);
  }

  /**
   * Tests the Drupal 6 upload settings to Drupal 8 field migration.
   */
  public function testUpload() {
    $field_storage = entity_load('field_storage_config', 'node.upload');
    $this->assertEqual($field_storage->id(), 'node.upload');
    $this->assertEqual(array('node', 'upload'), entity_load('migration', 'd6_upload_field')->getIdMap()->lookupDestinationID(array('')));
  }

}
