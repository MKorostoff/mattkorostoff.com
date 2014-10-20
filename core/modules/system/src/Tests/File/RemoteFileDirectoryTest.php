<?php

/**
 * @file
 * Definition of Drupal\system\Tests\File\RemoteFileDirectoryTest.
 */

namespace Drupal\system\Tests\File;

/**
 * Tests operations dealing with directories.
 *
 * @group File
 */
class RemoteFileDirectoryTest extends DirectoryTest {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('file_test');

  /**
   * A stream wrapper scheme to register for the test.
   *
   * @var string
   */
  protected $scheme = 'dummy-remote';

  /**
   * A fully-qualified stream wrapper class name to register for the test.
   *
   * @var string
   */
  protected $classname = 'Drupal\file_test\DummyRemoteStreamWrapper';

  protected function setUp() {
    parent::setUp();
    \Drupal::config('system.file')->set('default_scheme', 'dummy-remote')->save();
  }
}
