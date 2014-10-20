<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateUserPictureInstanceTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * User picture field instance migration.
 *
 * @group migrate_drupal
 */
class MigrateUserPictureInstanceTest extends MigrateDrupalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  static $modules = array('image');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Add some node mappings to get past checkRequirements().
    $id_mappings = array(
      'd6_user_picture_field' => array(
        array(array('user_upload'), array('name', 'bundle')),
      ),
    );
    $this->prepareMigrations($id_mappings);
    entity_create('field_storage_config', array(
      'entity_type' => 'user',
      'field_name' => 'user_picture',
      'type' => 'image',
      'translatable' => '0',
    ))->save();

    $migration = entity_load('migration', 'd6_user_picture_field_instance');
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests the Drupal 6 user picture to Drupal 8 picture field instance migration.
   */
  public function testUserPictureFieldInstance() {
    $field = entity_load('field_config', 'user.user.user_picture');
    $settings = $field->getSettings();
    $this->assertEqual($settings['file_extensions'], 'png gif jpg jpeg');
    $this->assertEqual($settings['file_directory'], 'pictures');
    $this->assertEqual($settings['max_filesize'], '30KB');
    $this->assertEqual($settings['max_resolution'], '85x85');

    $this->assertEqual(array('user', 'user', 'user_picture'), entity_load('migration', 'd6_user_picture_field_instance')->getIdMap()->lookupDestinationID(array('')));
  }

}
