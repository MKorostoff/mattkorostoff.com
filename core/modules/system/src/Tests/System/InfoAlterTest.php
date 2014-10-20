<?php

/**
 * @file
 * Definition of Drupal\system\Tests\System\InfoAlterTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests the effectiveness of hook_system_info_alter().
 *
 * @group system
 */
class InfoAlterTest extends DrupalUnitTestBase {

  public static $modules = array('system');

  /**
   * Tests that theme .info.yml data is rebuild after enabling a module.
   *
   * Tests that info data is rebuilt after a module that implements
   * hook_system_info_alter() is enabled. Also tests if core *_list() functions
   * return freshly altered info.
   */
  function testSystemInfoAlter() {
    \Drupal::state()->set('module_test.hook_system_info_alter', TRUE);
    $info = system_rebuild_module_data();
    $this->assertFalse(isset($info['node']->info['required']), 'Before the module_test is installed the node module is not required.');

    // Enable the test module.
    \Drupal::moduleHandler()->install(array('module_test'), FALSE);
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('module_test'), 'Test module is enabled.');

    $info = system_rebuild_module_data();
    $this->assertTrue($info['node']->info['required'], 'After the module_test is installed the node module is required.');
  }
}
