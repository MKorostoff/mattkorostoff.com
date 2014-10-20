<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Tests\LegacyTest.
 */

namespace Drupal\taxonomy\Tests;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Posts an article with a taxonomy term and a date prior to 1970.
 *
 * @group taxonomy
 */
class LegacyTest extends TaxonomyTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'datetime');

  protected function setUp() {
    parent::setUp();

    // Create a tags vocabulary for the 'article' content type.
    $vocabulary = entity_create('taxonomy_vocabulary', array(
      'name' => 'Tags',
      'vid' => 'tags',
    ));
    $vocabulary->save();
    $field_name = 'field_' . $vocabulary->id();

    entity_create('field_storage_config', array(
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'taxonomy_term_reference',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => array(
        'allowed_values' => array(
          array(
            'vocabulary' => $vocabulary->id(),
            'parent' => 0,
          ),
        ),
      ),
    ))->save();
    entity_create('field_config', array(
      'entity_type' => 'node',
      'field_name' => $field_name,
      'bundle' => 'article',
      'label' => 'Tags',
    ))->save();

    entity_get_form_display('node', 'article', 'default')
      ->setComponent($field_name, array(
        'type' => 'taxonomy_autocomplete',
      ))
      ->save();

    $this->admin_user = $this->drupalCreateUser(array('administer taxonomy', 'administer nodes', 'bypass node access'));
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Test taxonomy functionality with nodes prior to 1970.
   */
  function testTaxonomyLegacyNode() {
    // Posts an article with a taxonomy term and a date prior to 1970.
    $date = new DrupalDateTime('1969-01-01 00:00:00');
    $edit = array();
    $edit['title[0][value]'] = $this->randomMachineName();
    $edit['created[0][value][date]'] = $date->format('Y-m-d');
    $edit['created[0][value][time]'] = $date->format('H:i:s');
    $edit['body[0][value]'] = $this->randomMachineName();
    $edit['field_tags'] = $this->randomMachineName();
    $this->drupalPostForm('node/add/article', $edit, t('Save and publish'));
    // Checks that the node has been saved.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertEqual($node->getCreatedTime(), $date->getTimestamp(), 'Legacy node was saved with the right date.');
  }
}
