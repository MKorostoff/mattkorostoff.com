<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateUserContactSettingsTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Users contact settings migration.
 *
 * @group migrate_drupal
 */
class MigrateUserContactSettingsTest extends MigrateDrupalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6User.php',
    );
    $this->loadDumps($dumps);

    $id_mappings = array(
      'd6_user' => array(
        array(array(2), array(2)),
        array(array(8), array(8)),
        array(array(15), array(15)),
      ),
    );

    $this->prepareMigrations($id_mappings);

    // Migrate users.
    $migration = entity_load('migration', 'd6_user_contact_settings');
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests the Drupal6 user contact settings migration.
   */
  public function testUserContactSettings() {
    $user_data = \Drupal::service('user.data');
    $module = $key = 'contact';
    $uid = 2;
    $setting = $user_data->get($module, $uid, $key);
    $this->assertIdentical($setting, '1');

    $uid = 8;
    $setting = $user_data->get($module, $uid, $key);
    $this->assertIdentical($setting, '0');

    $uid = 15;
    $setting = $user_data->get($module, $uid, $key);
    $this->assertIdentical($setting, NULL);
  }

}
