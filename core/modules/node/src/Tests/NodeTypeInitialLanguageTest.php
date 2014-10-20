<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeTypeInitalLanguageTest.
 */

namespace Drupal\node\Tests;

/**
 * Tests node type initial language settings.
 *
 * @group node
 */
class NodeTypeInitialLanguageTest extends NodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language', 'field_ui');

  protected function setUp() {
    parent::setUp();

    $web_user = $this->drupalCreateUser(array('bypass node access', 'administer content types', 'administer node fields', 'administer node form display', 'administer node display', 'administer languages', 'administer site configuration'));
    $this->drupalLogin($web_user);
  }

  /**
   * Tests the node type initial language defaults, and modifies them.
   *
   * The default initial language must be the site's default, and the language
   * locked option must be on.
   */
  function testNodeTypeInitialLanguageDefaults() {
    $this->drupalGet('admin/structure/types/manage/article');
    $this->assertOptionSelected('edit-language-configuration-langcode', 'site_default', 'The default initial language is the site default.');
    $this->assertNoFieldChecked('edit-language-configuration-language-show', 'Language selector is hidden by default.');

    // Tests if the language field cannot be rearranged on the manage fields tab.
    $this->drupalGet('admin/structure/types/manage/article/fields');
    $language_field = $this->xpath('//*[@id="field-overview"]/*[@id="language"]');
    $this->assert(empty($language_field), 'Language field is not visible on manage fields tab.');

    $this->drupalGet('node/add/article');
    $this->assertNoField('langcode', 'Language is not selectable on node add/edit page by default.');

    // Adds a new language and set it as default.
    $edit = array(
      'predefined_langcode' => 'hu',
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));
    $edit = array(
      'site_default_language' => 'hu',
    );
    $this->drupalPostForm('admin/config/regional/settings', $edit, t('Save configuration'));

    // Tests the initial language after changing the site default language.
    // First unhide the language selector.
    $edit = array(
      'language_configuration[language_show]' => TRUE,
    );
    $this->drupalPostForm('admin/structure/types/manage/article', $edit, t('Save content type'));
    $this->drupalGet('node/add/article');
    $this->assertField('langcode', 'Language is selectable on node add/edit page when language not hidden.');
    $this->assertOptionSelected('edit-langcode', 'hu', 'The initial language is the site default on the node add page after the site default language is changed.');

    // Tests if the language field can be rearranged on the manage form display
    // tab.
    $this->drupalGet('admin/structure/types/manage/article/form-display');
    $language_field = $this->xpath('//*[@id="langcode"]');
    $this->assert(!empty($language_field), 'Language field is visible on manage form display tab.');

    // Tests if the language field can be rearranged on the manage display tab.
    $this->drupalGet('admin/structure/types/manage/article/display');
    $language_display = $this->xpath('//*[@id="langcode"]');
    $this->assert(!empty($language_display), 'Language field is visible on manage display tab.');
    // Tests if the language field is hidden by default.
    $this->assertOptionSelected('edit-fields-langcode-type', 'hidden', 'Language is hidden by default on manage display tab.');

    // Changes the initial language settings.
    $edit = array(
      'language_configuration[langcode]' => 'en',
    );
    $this->drupalPostForm('admin/structure/types/manage/article', $edit, t('Save content type'));
    $this->drupalGet('node/add/article');
    $this->assertOptionSelected('edit-langcode', 'en', 'The initial language is the defined language.');
  }

  /**
   * Tests language field visibility features.
   */
  function testLanguageFieldVisibility() {
    // Creates a node to test Language field visibility feature.
    $edit = array(
      'title[0][value]' => $this->randomMachineName(8),
      'body[0][value]' => $this->randomMachineName(16),
    );
    $this->drupalPostForm('node/add/article', $edit, t('Save'));
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertTrue($node, 'Node found in database.');

    // Loads node page and check if Language field is hidden by default.
    $this->drupalGet('node/' . $node->id());
    $language_field = $this->xpath('//div[@id=:id]/div', array(
      ':id' => 'field-language-display',
    ));
    $this->assertTrue(empty($language_field), 'Language field value is not shown by default on node page.');

    // Changes Language field visibility to true and check if it is saved.
    $edit = array(
      'fields[langcode][type]' => 'visible',
    );
    $this->drupalPostForm('admin/structure/types/manage/article/display', $edit, t('Save'));
    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->assertOptionSelected('edit-fields-langcode-type', 'visible', 'Language field has been set to visible.');

    // Loads node page and check if Language field is shown.
    $this->drupalGet('node/' . $node->id());
    $language_field = $this->xpath('//div[@id=:id]/div', array(
      ':id' => 'field-language-display',
    ));
    $this->assertFalse(empty($language_field), 'Language field value is shown on node page.');
  }
}
