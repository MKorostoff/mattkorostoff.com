<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Bootstrap\GetFilenameUnitTest.
 */

namespace Drupal\system\Tests\Bootstrap;

use Drupal\simpletest\KernelTestBase;

/**
 * Tests that drupal_get_filename() works correctly.
 *
 * @group Bootstrap
 */
class GetFilenameUnitTest extends KernelTestBase {

  protected function setUp() {
    parent::setUp();
    $this->container = NULL;
    \Drupal::setContainer(NULL);
  }

  /**
   * Tests that drupal_get_filename() works when the file is not in database.
   */
  function testDrupalGetFilename() {
    // drupal_get_profile() is using obtaining the profile from state if the
    // install_state global is not set.
    global $install_state;
    $install_state['parameters']['profile'] = 'testing';

    // Assert that this test is meaningful.
    $this->assertNull($this->container);
    $this->assertNull(\Drupal::getContainer());

    // Retrieving the location of a module.
    $this->assertIdentical(drupal_get_filename('module', 'system'), 'core/modules/system/system.info.yml');

    // Retrieving the location of a theme.
    $this->assertIdentical(drupal_get_filename('theme', 'stark'), 'core/themes/stark/stark.info.yml');

    // Retrieving the location of a theme engine.
    $this->assertIdentical(drupal_get_filename('theme_engine', 'phptemplate'), 'core/themes/engines/phptemplate/phptemplate.info.yml');

    // Retrieving the location of a profile. Profiles are a special case with
    // a fixed location and naming.
    $this->assertIdentical(drupal_get_filename('profile', 'standard'), 'core/profiles/standard/standard.info.yml');

    // Searching for an item that does not exist returns NULL.
    $this->assertNull(drupal_get_filename('module', uniqid("", TRUE)), 'Searching for an item that does not exist returns NULL.');
  }
}
