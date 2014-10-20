<?php

/**
 * @file
 * Contains \Drupal\language\Tests\LanguageCustomConfigurationTest.
 */

namespace Drupal\language\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;

/**
 * Adds and configures custom languages.
 *
 * @group language
 */
class LanguageCustomLanguageConfigurationTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language');

  /**
   * Functional tests for adding, editing and deleting languages.
   */
  public function testLanguageConfiguration() {

    // Create user with permissions to add and remove languages.
    $admin_user = $this->drupalCreateUser(array('administer languages', 'access administration pages'));
    $this->drupalLogin($admin_user);

    // Add custom language.
    $edit = array(
      'predefined_langcode' => 'custom',
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));
    // Test validation on missing values.
    $this->assertText(t('!name field is required.', array('!name' => t('Language code'))));
    $this->assertText(t('!name field is required.', array('!name' => t('Language name in English'))));
    $empty_language = new Language();
    $this->assertFieldChecked('edit-direction-' . $empty_language->direction, 'Consistent usage of language direction.');
    $this->assertUrl(\Drupal::url('language.add', array(), array('absolute' => TRUE)), [], 'Correct page redirection.');

    // Test validation of invalid values.
    $edit = array(
      'predefined_langcode' => 'custom',
      'langcode' => 'white space',
      'label' => '<strong>evil markup</strong>',
      'direction' => LanguageInterface::DIRECTION_LTR,
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));
    $this->assertRaw(t('%field may only contain characters a-z, underscores, or hyphens.', array('%field' => t('Language code'))));
    $this->assertRaw(t('%field cannot contain any markup.', array('%field' => t('Language name in English'))));
    $this->assertUrl(\Drupal::url('language.add', array(), array('absolute' => TRUE)), [], 'Correct page redirection.');

    // Test validation of existing language values.
    $edit = array(
      'predefined_langcode' => 'custom',
      'langcode' => 'de',
      'label' => 'German',
      'direction' => LanguageInterface::DIRECTION_LTR,
    );

    // Add the language the first time.
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));
    $this->assertRaw(t(
      'The language %language has been created and can now be used.',
      array('%language' => $edit['label'])
    ));
    $this->assertUrl(\Drupal::url('language.admin_overview', array(), array('absolute' => TRUE)), [], 'Correct page redirection.');

    // Add the language a second time and confirm that this is not allowed.
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));
    $this->assertRaw(t(
      'The language %language (%langcode) already exists.',
      array('%language' => $edit['label'], '%langcode' => $edit['langcode'])
    ));
    $this->assertUrl(\Drupal::url('language.add', array(), array('absolute' => TRUE)), [], 'Correct page redirection.');
  }
}
