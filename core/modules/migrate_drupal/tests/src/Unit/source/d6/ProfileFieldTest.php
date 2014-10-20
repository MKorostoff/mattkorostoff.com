<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate_drupal\Unit\source\d6\ProfileFieldTest.
 */

namespace Drupal\Tests\migrate_drupal\Unit\source\d6;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D6 profile field source plugin.
 *
 * @group migrate_drupal
 */
class ProfileFieldTest extends MigrateSqlSourceTestCase {

  // The plugin system is not working during unit testing so the source plugin
  // class needs to be manually specified.
  const PLUGIN_CLASS = 'Drupal\migrate_drupal\Plugin\migrate\source\d6\ProfileField';

  // The fake Migration configuration entity.
  protected $migrationConfiguration = array(
    // The id of the entity, can be any string.
    'id' => 'test_profile_fields',
    // Leave it empty for now.
    'idlist' => array(),
    'source' => array(
      'plugin' => 'd6_profile_field',
    ),
  );

  // We need to set up the database contents; it's easier to do that below.
  // These are sample result queries.
  // @todo Add multiple cases.
  protected $expectedResults = array(
    array(
      'fid' => 1,
      'title' => 'First name',
      'name' => 'profile_first_name',
      'explanation' => 'First name user',
      'category' => 'profile',
      'page' => '',
      'type' => 'textfield',
      'weight' => 0,
      'required' => 1,
      'register' => 0,
      'visibility' => 2,
      'autocomplete' => 0,
      'options' => array(),
    ),
    array(
      'fid' => 2,
      'title' => 'Last name',
      'name' => 'profile_last_name',
      'explanation' => 'Last name user',
      'category' => 'profile',
      'page' => '',
      'type' => 'textfield',
      'weight' => 0,
      'required' => 0,
      'register' => 0,
      'visibility' => 2,
      'autocomplete' => 0,
      'options' => array(),
    ),
    array(
      'fid' => 3,
      'title' => 'Policy',
      'name' => 'profile_policy',
      'explanation' => 'A checkbox that say if you accept policy of website',
      'category' => 'profile',
      'page' => '',
      'type' => 'checkbox',
      'weight' => 0,
      'required' => 1,
      'register' => 1,
      'visibility' => 2,
      'autocomplete' => 0,
      'options' => array(),
    ),
  );

  /**
   * Prepopulate contents with results.
   */
  protected function setUp() {
    $this->databaseContents['profile_fields'] = $this->expectedResults;
    parent::setUp();
  }

}

namespace Drupal\Tests\migrate_drupal\Unit\source\d6;

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\migrate_drupal\Plugin\migrate\source\d6\ProfileField;

class TestProfileField extends ProfileField {
  public function setDatabase(Connection $database) {
    $this->database = $database;
  }
  public function setModuleHandler(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }
}
