<?php

/**
 * @file
 * Contains \Drupal\field\Tests\Views\ApiDataTest.
 */

namespace Drupal\field\Tests\Views;

/**
 * Tests the Field Views data.
 *
 * @group field
 */
class ApiDataTest extends FieldTestBase {

  protected function setUp() {
    parent::setUp();

    $field_names = $this->setUpFieldStorages(1);

    // Attach the field to nodes only.
    $field = array(
      'field_name' => $field_names[0],
      'entity_type' => 'node',
      'bundle' => 'page',
    );
    entity_create('field_config', $field)->save();

    // Now create some example nodes/users for the view result.
    for ($i = 0; $i < 5; $i++) {
      $edit = array(
        $field_names[0] => array((array('value' => $this->randomMachineName()))),
      );
      $nodes[] = $this->drupalCreateNode($edit);
    }

    $this->container->get('views.views_data')->clear();
  }

  /**
   * Unit testing the views data structure.
   *
   * We check data structure for both node and node revision tables.
   */
  function testViewsData() {
    $views_data = $this->container->get('views.views_data');
    $data = array();

    // Check the table and the joins of the first field.
    // Attached to node only.
    $field_storage = $this->fieldStorages[0];
    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    $table_mapping = \Drupal::entityManager()->getStorage('node')->getTableMapping();
    $current_table = $table_mapping->getDedicatedDataTableName($field_storage);
    $revision_table = $table_mapping->getDedicatedRevisionTableName($field_storage);
    $data[$current_table] = $views_data->get($current_table);
    $data[$revision_table] = $views_data->get($revision_table);

    $this->assertTrue(isset($data[$current_table]));
    $this->assertTrue(isset($data[$revision_table]));
    // The node field should join against node.
    $this->assertTrue(isset($data[$current_table]['table']['join']['node']));
    $this->assertTrue(isset($data[$revision_table]['table']['join']['node_revision']));

    $expected_join = array(
      'left_table' => 'node_field_data',
      'left_field' => 'nid',
      'field' => 'entity_id',
      'extra' => array(
        array('field' => 'deleted', 'value' => 0, 'numeric' => TRUE),
        array('left_field' => 'langcode', 'field' => 'langcode'),
      ),
    );
    $this->assertEqual($expected_join, $data[$current_table]['table']['join']['node']);
    $expected_join = array(
      'left_table' => 'node_field_revision',
      'left_field' => 'vid',
      'field' => 'revision_id',
      'extra' => array(
        array('field' => 'deleted', 'value' => 0, 'numeric' => TRUE),
        array('left_field' => 'langcode', 'field' => 'langcode'),
      ),
    );
    $this->assertEqual($expected_join, $data[$revision_table]['table']['join']['node_revision']);
  }

}
