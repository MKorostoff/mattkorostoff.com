<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateViewModesTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Migrate view modes.
 *
 * @group migrate_drupal
 */
class MigrateViewModesTest extends MigrateDrupalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_view_modes');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6FieldInstance.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests Drupal 6 view modes to Drupal 8 migration.
   */
  public function testViewModes() {
    // Test a new view mode.
    $view_mode = EntityViewMode::load('node.preview');
    $this->assertEqual(is_null($view_mode), FALSE, 'Preview view mode loaded.');
    $this->assertEqual($view_mode->label(), 'Preview', 'View mode has correct label.');
    // Test the Id Map.
    $this->assertEqual(array('node', 'preview'), entity_load('migration', 'd6_view_modes')->getIdMap()->lookupDestinationID(array(1)));
  }

}
