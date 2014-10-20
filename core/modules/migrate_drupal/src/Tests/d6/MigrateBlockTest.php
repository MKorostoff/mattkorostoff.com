<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateBlockTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;
use Drupal\block\Entity\Block;

/**
 * Upgrade block settings to block.block.*.yml.
 *
 * @group migrate_drupal
 */
class MigrateBlockTest extends MigrateDrupalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  static $modules = array(
    'block',
    'views',
    'comment',
    'menu_ui',
    'block_content',
    'node',
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $entities = array(
      entity_create('menu', array('id' => 'primary-links')),
      entity_create('menu', array('id' => 'secondary-links')),
      entity_create('block_content', array('id' => 1, 'type' => 'basic', 'info' => $this->randomMachineName(8))),
      entity_create('block_content', array('id' => 2, 'type' => 'basic', 'info' => $this->randomMachineName(8))),
    );
    foreach ($entities as $entity) {
      $entity->enforceIsNew(TRUE);
      $entity->save();
    }
    $this->prepareMigrations(array(
      'd6_custom_block'  => array(
        array(array(10), array(1)),
        array(array(11), array(2)),
        array(array(12), array(1)),
        array(array(13), array(2)),
      ),
      'd6_menu' => array(
        array(array('menu1'), array('menu')),
      ),
    ));

    // Set Bartik and Seven as the default public and admin theme.
    $config = \Drupal::config('system.theme');
    $config->set('default', 'bartik');
    $config->set('admin', 'seven');
    $config->save();

    // Install one of D8's test themes.
    \Drupal::service('theme_handler')->install(array('test_theme'));

    /** @var \Drupal\migrate\entity\Migration $migration */
    $migration = entity_load('migration', 'd6_block');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6Block.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Test the block settings migration.
   */
  public function testBlockMigration() {
    $blocks = Block::loadMultiple();
    $this->assertEqual(count($blocks), 8);

    // User blocks
    $test_block_user = $blocks['user'];
    $this->assertNotNull($test_block_user);
    $this->assertEqual('sidebar_first', $test_block_user->get('region'));
    $this->assertEqual('bartik', $test_block_user->get('theme'));
    $visibility = $test_block_user->getVisibility();
    $this->assertEqual(TRUE, $visibility['request_path']['negate']);
    $this->assertEqual('', $visibility['request_path']['pages']);
    $this->assertEqual(0, $test_block_user->weight);

    $test_block_user_1 = $blocks['user_1'];
    $this->assertNotNull($test_block_user_1);
    $this->assertEqual('sidebar_first', $test_block_user_1->get('region'));
    $this->assertEqual('bartik', $test_block_user_1->get('theme'));
    $visibility = $test_block_user_1->getVisibility();
    $this->assertEqual(TRUE, $visibility['request_path']['negate']);
    $this->assertEqual('', $visibility['request_path']['pages']);
    $this->assertEqual(0, $test_block_user_1->weight);

    // Check system block
    $test_block_system = $blocks['system'];
    $this->assertNotNull($test_block_system);
    $this->assertEqual('footer', $test_block_system->get('region'));
    $this->assertEqual('bartik', $test_block_system->get('theme'));
    $visibility = $test_block_system->getVisibility();
    $this->assertEqual(TRUE, $visibility['request_path']['negate']);
    $this->assertEqual('', $visibility['request_path']['pages']);
    $this->assertEqual(-5, $test_block_system->weight);

    // Check menu blocks
    $test_block_menu = $blocks['menu'];
    $this->assertNotNull($test_block_menu);
    $this->assertEqual('header', $test_block_menu->get('region'));
    $this->assertEqual('bartik', $test_block_menu->get('theme'));
    $visibility = $test_block_menu->getVisibility();
    $this->assertEqual(TRUE, $visibility['request_path']['negate']);
    $this->assertEqual('', $visibility['request_path']['pages']);
    $this->assertEqual(-5, $test_block_menu->weight);

    // Check custom blocks
    $test_block_block = $blocks['block'];
    $this->assertNotNull($test_block_block);
    $this->assertEqual('content', $test_block_block->get('region'));
    $this->assertEqual('bartik', $test_block_block->get('theme'));
    $visibility = $test_block_block->getVisibility();
    $this->assertEqual(FALSE, $visibility['request_path']['negate']);
    $this->assertEqual('<front>', $visibility['request_path']['pages']);
    $this->assertEqual(0, $test_block_block->weight);

    $test_block_block_1 = $blocks['block_1'];
    $this->assertNotNull($test_block_block_1);
    $this->assertEqual('right', $test_block_block_1->get('region'));
    $this->assertEqual('bluemarine', $test_block_block_1->get('theme'));
    $visibility = $test_block_block_1->getVisibility();
    $this->assertEqual(FALSE, $visibility['request_path']['negate']);
    $this->assertEqual('node', $visibility['request_path']['pages']);
    $this->assertEqual(-4, $test_block_block_1->weight);

    $test_block_block_2 = $blocks['block_2'];
    $this->assertNotNull($test_block_block_2);
    $this->assertEqual('right', $test_block_block_2->get('region'));
    $this->assertEqual('test_theme', $test_block_block_2->get('theme'));
    $visibility = $test_block_block_2->getVisibility();
    $this->assertEqual(TRUE, $visibility['request_path']['negate']);
    $this->assertEqual('', $visibility['request_path']['pages']);
    $this->assertEqual(-7, $test_block_block_2->weight);

    $test_block_block_3 = $blocks['block_3'];
    $this->assertNotNull($test_block_block_3);
    $this->assertEqual('left', $test_block_block_3->get('region'));
    $this->assertEqual('test_theme', $test_block_block_3->get('theme'));
    $visibility = $test_block_block_3->getVisibility();
    $this->assertEqual(TRUE, $visibility['request_path']['negate']);
    $this->assertEqual('', $visibility['request_path']['pages']);
    $this->assertEqual(-2, $test_block_block_3->weight);
  }
}
