<?php

/**
 * @file
 * Contains Drupal\system\Tests\Entity\EntityDefinitionUpdateTest.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\Exception\FieldStorageDefinitionUpdateForbiddenException;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\entity_test\FieldStorageDefinition;

/**
 * Tests EntityDefinitionUpdateManager functionality.
 *
 * @group Entity
 */
class EntityDefinitionUpdateTest extends EntityUnitTestBase {

  /**
   * The entity definition update manager.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $entityDefinitionUpdateManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->entityDefinitionUpdateManager = $this->container->get('entity.definition_update_manager');
    $this->database = $this->container->get('database');

    // Install every entity type's schema that wasn't installed in the parent
    // method.
    foreach (array_diff_key($this->entityManager->getDefinitions(), array_flip(array('user', 'entity_test'))) as $entity_type_id => $entity_type) {
      $this->installEntitySchema($entity_type_id);
    }
  }

  /**
   * Tests when no definition update is needed.
   */
  public function testNoUpdates() {
    // Ensure that the definition update manager reports no updates.
    $this->assertFalse($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that no updates are needed.');
    $this->assertIdentical($this->entityDefinitionUpdateManager->getChangeSummary(), array(), 'EntityDefinitionUpdateManager reports an empty change summary.');

    // Ensure that applyUpdates() runs without error (it's not expected to do
    // anything when there aren't updates).
    $this->entityDefinitionUpdateManager->applyUpdates();
  }

  /**
   * Tests updating entity schema when there are no existing entities.
   */
  public function testEntityTypeUpdateWithoutData() {
    // The 'entity_test_update' entity type starts out non-revisionable, so
    // ensure the revision table hasn't been created during setUp().
    $this->assertFalse($this->database->schema()->tableExists('entity_test_update_revision'), 'Revision table not created for entity_test_update.');

    // Update it to be revisionable and ensure the definition update manager
    // reports that an update is needed.
    $this->updateEntityTypeToRevisionable();
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $expected = array(
      'entity_test_update' => array(
        t('Update the %entity_type entity type.', array('%entity_type' => $this->entityManager->getDefinition('entity_test_update')->getLabel())),
      ),
    );
    $this->assertIdentical($this->entityDefinitionUpdateManager->getChangeSummary(), $expected); //, 'EntityDefinitionUpdateManager reports the expected change summary.');

    // Run the update and ensure the revision table is created.
    $this->entityDefinitionUpdateManager->applyUpdates();
    $this->assertTrue($this->database->schema()->tableExists('entity_test_update_revision'), 'Revision table created for entity_test_update.');
  }

  /**
   * Tests updating entity schema when there are existing entities.
   */
  public function testEntityTypeUpdateWithData() {
    // Save an entity.
    $this->entityManager->getStorage('entity_test_update')->create()->save();

    // Update the entity type to be revisionable and try to apply the update.
    // It's expected to throw an exception.
    $this->updateEntityTypeToRevisionable();
    try {
      $this->entityDefinitionUpdateManager->applyUpdates();
      $this->fail('EntityStorageException thrown when trying to apply an update that requires data migration.');
    }
    catch (EntityStorageException $e) {
      $this->pass('EntityStorageException thrown when trying to apply an update that requires data migration.');
    }
  }

  /**
   * Tests creating, updating, and deleting a base field if no entities exist.
   */
  public function testBaseFieldCreateUpdateDeleteWithoutData() {
    // Add a base field, ensure the update manager reports it, and the update
    // creates its schema.
    $this->addBaseField();
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $expected = array(
      'entity_test_update' => array(
        t('Create the %field_name field.', array('%field_name' => t('A new base field'))),
      ),
    );
    $this->assertIdentical($this->entityDefinitionUpdateManager->getChangeSummary(), $expected, 'EntityDefinitionUpdateManager reports the expected change summary.');
    $this->entityDefinitionUpdateManager->applyUpdates();
    $this->assertTrue($this->database->schema()->fieldExists('entity_test_update', 'new_base_field'), 'Column created in shared table for new_base_field.');

    // Add an index on the base field, ensure the update manager reports it,
    // and the update creates it.
    $this->addBaseFieldIndex();
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $expected = array(
      'entity_test_update' => array(
        t('Update the %field_name field.', array('%field_name' => t('A new base field'))),
      ),
    );
    $this->assertIdentical($this->entityDefinitionUpdateManager->getChangeSummary(), $expected, 'EntityDefinitionUpdateManager reports the expected change summary.');
    $this->entityDefinitionUpdateManager->applyUpdates();
    $this->assertTrue($this->database->schema()->indexExists('entity_test_update', 'entity_test_update_field__new_base_field'), 'Index created.');

    // Remove the above index, ensure the update manager reports it, and the
    // update deletes it.
    $this->removeBaseFieldIndex();
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $expected = array(
      'entity_test_update' => array(
        t('Update the %field_name field.', array('%field_name' => t('A new base field'))),
      ),
    );
    $this->assertIdentical($this->entityDefinitionUpdateManager->getChangeSummary(), $expected, 'EntityDefinitionUpdateManager reports the expected change summary.');
    $this->entityDefinitionUpdateManager->applyUpdates();
    $this->assertFalse($this->database->schema()->indexExists('entity_test_update', 'entity_test_update_field__new_base_field'), 'Index deleted.');

    // Update the type of the base field from 'string' to 'text', ensure the
    // update manager reports it, and the update adjusts the schema
    // accordingly.
    $this->modifyBaseField();
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $expected = array(
      'entity_test_update' => array(
        t('Update the %field_name field.', array('%field_name' => t('A new base field'))),
      ),
    );
    $this->assertIdentical($this->entityDefinitionUpdateManager->getChangeSummary(), $expected, 'EntityDefinitionUpdateManager reports the expected change summary.');
    $this->entityDefinitionUpdateManager->applyUpdates();
    $this->assertFalse($this->database->schema()->fieldExists('entity_test_update', 'new_base_field'), 'Original column deleted in shared table for new_base_field.');
    $this->assertTrue($this->database->schema()->fieldExists('entity_test_update', 'new_base_field__value'), 'Value column created in shared table for new_base_field.');
    $this->assertTrue($this->database->schema()->fieldExists('entity_test_update', 'new_base_field__format'), 'Format column created in shared table for new_base_field.');

    // Remove the base field, ensure the update manager reports it, and the
    // update deletes the schema.
    $this->removeBaseField();
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $expected = array(
      'entity_test_update' => array(
        t('Delete the %field_name field.', array('%field_name' => t('A new base field'))),
      ),
    );
    $this->assertIdentical($this->entityDefinitionUpdateManager->getChangeSummary(), $expected, 'EntityDefinitionUpdateManager reports the expected change summary.');
    $this->entityDefinitionUpdateManager->applyUpdates();
    $this->assertFalse($this->database->schema()->fieldExists('entity_test_update', 'new_base_field_value'), 'Value column deleted from shared table for new_base_field.');
    $this->assertFalse($this->database->schema()->fieldExists('entity_test_update', 'new_base_field_format'), 'Format column deleted from shared table for new_base_field.');
  }

  /**
   * Tests creating, updating, and deleting a bundle field if no entities exist.
   */
  public function testBundleFieldCreateUpdateDeleteWithoutData() {
    // Add a bundle field, ensure the update manager reports it, and the update
    // creates its schema.
    $this->addBundleField();
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $expected = array(
      'entity_test_update' => array(
        t('Create the %field_name field.', array('%field_name' => t('A new bundle field'))),
      ),
    );
    $this->assertIdentical($this->entityDefinitionUpdateManager->getChangeSummary(), $expected, 'EntityDefinitionUpdateManager reports the expected change summary.');
    $this->entityDefinitionUpdateManager->applyUpdates();
    $this->assertTrue($this->database->schema()->tableExists('entity_test_update__new_bundle_field'), 'Dedicated table created for new_bundle_field.');

    // Update the type of the base field from 'string' to 'text', ensure the
    // update manager reports it, and the update adjusts the schema
    // accordingly.
    $this->modifyBundleField();
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $expected = array(
      'entity_test_update' => array(
        t('Update the %field_name field.', array('%field_name' => t('A new bundle field'))),
      ),
    );
    $this->assertIdentical($this->entityDefinitionUpdateManager->getChangeSummary(), $expected, 'EntityDefinitionUpdateManager reports the expected change summary.');
    $this->entityDefinitionUpdateManager->applyUpdates();
    $this->assertTrue($this->database->schema()->fieldExists('entity_test_update__new_bundle_field', 'new_bundle_field_format'), 'Format column created in dedicated table for new_base_field.');

    // Remove the bundle field, ensure the update manager reports it, and the
    // update deletes the schema.
    $this->removeBundleField();
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $expected = array(
      'entity_test_update' => array(
        t('Delete the %field_name field.', array('%field_name' => t('A new bundle field'))),
      ),
    );
    $this->assertIdentical($this->entityDefinitionUpdateManager->getChangeSummary(), $expected, 'EntityDefinitionUpdateManager reports the expected change summary.');
    $this->entityDefinitionUpdateManager->applyUpdates();
    $this->assertFalse($this->database->schema()->tableExists('entity_test_update__new_bundle_field'), 'Dedicated table deleted for new_bundle_field.');
  }

  /**
   * Tests creating and deleting a base field if entities exist.
   *
   * This tests deletion when there are existing entities, but not existing data
   * for the field being deleted.
   *
   * @see testBaseFieldDeleteWithExistingData()
   */
  public function testBaseFieldCreateDeleteWithExistingEntities() {
    // Save an entity.
    $name = $this->randomString();
    $entity = $this->entityManager->getStorage('entity_test_update')->create(array('name' => $name));
    $entity->save();

    // Add a base field and run the update. Ensure the base field's column is
    // created and the prior saved entity data is still there.
    $this->addBaseField();
    $this->entityDefinitionUpdateManager->applyUpdates();
    $this->assertTrue($this->database->schema()->fieldExists('entity_test_update', 'new_base_field'), 'Column created in shared table for new_base_field.');
    $entity = $this->entityManager->getStorage('entity_test_update')->load($entity->id());
    $this->assertIdentical($entity->name->value, $name, 'Entity data preserved during field creation.');

    // Remove the base field and run the update. Ensure the base field's column
    // is deleted and the prior saved entity data is still there.
    $this->removeBaseField();
    $this->entityDefinitionUpdateManager->applyUpdates();
    $this->assertFalse($this->database->schema()->fieldExists('entity_test_update', 'new_base_field'), 'Column deleted from shared table for new_base_field.');
    $entity = $this->entityManager->getStorage('entity_test_update')->load($entity->id());
    $this->assertIdentical($entity->name->value, $name, 'Entity data preserved during field deletion.');
  }

  /**
   * Tests creating and deleting a bundle field if entities exist.
   *
   * This tests deletion when there are existing entities, but not existing data
   * for the field being deleted.
   *
   * @see testBundleFieldDeleteWithExistingData()
   */
  public function testBundleFieldCreateDeleteWithExistingEntities() {
    // Save an entity.
    $name = $this->randomString();
    $entity = $this->entityManager->getStorage('entity_test_update')->create(array('name' => $name));
    $entity->save();

    // Add a bundle field and run the update. Ensure the bundle field's table
    // is created and the prior saved entity data is still there.
    $this->addBundleField();
    $this->entityDefinitionUpdateManager->applyUpdates();
    $this->assertTrue($this->database->schema()->tableExists('entity_test_update__new_bundle_field'), 'Dedicated table created for new_bundle_field.');
    $entity = $this->entityManager->getStorage('entity_test_update')->load($entity->id());
    $this->assertIdentical($entity->name->value, $name, 'Entity data preserved during field creation.');

    // Remove the base field and run the update. Ensure the bundle field's
    // table is deleted and the prior saved entity data is still there.
    $this->removeBundleField();
    $this->entityDefinitionUpdateManager->applyUpdates();
    $this->assertFalse($this->database->schema()->tableExists('entity_test_update__new_bundle_field'), 'Dedicated table deleted for new_bundle_field.');
    $entity = $this->entityManager->getStorage('entity_test_update')->load($entity->id());
    $this->assertIdentical($entity->name->value, $name, 'Entity data preserved during field deletion.');
  }

  /**
   * Tests deleting a base field when it has existing data.
   */
  public function testBaseFieldDeleteWithExistingData() {
    // Add the base field and run the update.
    $this->addBaseField();
    $this->entityDefinitionUpdateManager->applyUpdates();

    // Save an entity with the base field populated.
    $this->entityManager->getStorage('entity_test_update')->create(array('new_base_field' => 'foo'))->save();

    // Remove the base field and apply updates. It's expected to throw an
    // exception.
    // @todo Revisit that expectation once purging is implemented for
    //   all fields: https://www.drupal.org/node/2282119.
    $this->removeBaseField();
    try {
      $this->entityDefinitionUpdateManager->applyUpdates();
      $this->fail('FieldStorageDefinitionUpdateForbiddenException thrown when trying to apply an update that deletes a non-purgeable field with data.');
    }
    catch (FieldStorageDefinitionUpdateForbiddenException $e) {
      $this->pass('FieldStorageDefinitionUpdateForbiddenException thrown when trying to apply an update that deletes a non-purgeable field with data.');
    }
  }

  /**
   * Tests deleting a bundle field when it has existing data.
   */
  public function testBundleFieldDeleteWithExistingData() {
    // Add the bundle field and run the update.
    $this->addBundleField();
    $this->entityDefinitionUpdateManager->applyUpdates();

    // Save an entity with the bundle field populated.
    entity_test_create_bundle('custom');
    $this->entityManager->getStorage('entity_test_update')->create(array('type' => 'test_bundle', 'new_bundle_field' => 'foo'))->save();

    // Remove the bundle field and apply updates. It's expected to throw an
    // exception.
    // @todo Revisit that expectation once purging is implemented for
    //   all fields: https://www.drupal.org/node/2282119.
    $this->removeBundleField();
    try {
      $this->entityDefinitionUpdateManager->applyUpdates();
      $this->fail('FieldStorageDefinitionUpdateForbiddenException thrown when trying to apply an update that deletes a non-purgeable field with data.');
    }
    catch (FieldStorageDefinitionUpdateForbiddenException $e) {
      $this->pass('FieldStorageDefinitionUpdateForbiddenException thrown when trying to apply an update that deletes a non-purgeable field with data.');
    }
  }

  /**
   * Tests updating a base field when it has existing data.
   */
  public function testBaseFieldUpdateWithExistingData() {
    // Add the base field and run the update.
    $this->addBaseField();
    $this->entityDefinitionUpdateManager->applyUpdates();

    // Save an entity with the base field populated.
    $this->entityManager->getStorage('entity_test_update')->create(array('new_base_field' => 'foo'))->save();

    // Change the field's field type and apply updates. It's expected to
    // throw an exception.
    $this->modifyBaseField();
    try {
      $this->entityDefinitionUpdateManager->applyUpdates();
      $this->fail('FieldStorageDefinitionUpdateForbiddenException thrown when trying to update a field schema that has data.');
    }
    catch (FieldStorageDefinitionUpdateForbiddenException $e) {
      $this->pass('FieldStorageDefinitionUpdateForbiddenException thrown when trying to update a field schema that has data.');
    }
  }

  /**
   * Tests updating a bundle field when it has existing data.
   */
  public function testBundleFieldUpdateWithExistingData() {
    // Add the bundle field and run the update.
    $this->addBundleField();
    $this->entityDefinitionUpdateManager->applyUpdates();

    // Save an entity with the bundle field populated.
    entity_test_create_bundle('custom');
    $this->entityManager->getStorage('entity_test_update')->create(array('type' => 'test_bundle', 'new_bundle_field' => 'foo'))->save();

    // Change the field's field type and apply updates. It's expected to
    // throw an exception.
    $this->modifyBundleField();
    try {
      $this->entityDefinitionUpdateManager->applyUpdates();
      $this->fail('FieldStorageDefinitionUpdateForbiddenException thrown when trying to update a field schema that has data.');
    }
    catch (FieldStorageDefinitionUpdateForbiddenException $e) {
      $this->pass('FieldStorageDefinitionUpdateForbiddenException thrown when trying to update a field schema that has data.');
    }
  }

  /**
   * Tests creating and deleting a multi-field index when there are no existing entities.
   */
  public function testEntityIndexCreateDeleteWithoutData() {
    // Add an entity index and ensure the update manager reports that as an
    // update to the entity type.
    $this->addEntityIndex();
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $expected = array(
      'entity_test_update' => array(
        t('Update the %entity_type entity type.', array('%entity_type' => $this->entityManager->getDefinition('entity_test_update')->getLabel())),
      ),
    );
    $this->assertIdentical($this->entityDefinitionUpdateManager->getChangeSummary(), $expected, 'EntityDefinitionUpdateManager reports the expected change summary.');

    // Run the update and ensure the new index is created.
    $this->entityDefinitionUpdateManager->applyUpdates();
    $this->assertTrue($this->database->schema()->indexExists('entity_test_update', 'entity_test_update__new_index'), 'Index created.');

    // Remove the index and ensure the update manager reports that as an
    // update to the entity type.
    $this->removeEntityIndex();
    $this->assertTrue($this->entityDefinitionUpdateManager->needsUpdates(), 'EntityDefinitionUpdateManager reports that updates are needed.');
    $expected = array(
      'entity_test_update' => array(
        t('Update the %entity_type entity type.', array('%entity_type' => $this->entityManager->getDefinition('entity_test_update')->getLabel())),
      ),
    );
    $this->assertIdentical($this->entityDefinitionUpdateManager->getChangeSummary(), $expected, 'EntityDefinitionUpdateManager reports the expected change summary.');

    // Run the update and ensure the index is deleted.
    $this->entityDefinitionUpdateManager->applyUpdates();
    $this->assertFalse($this->database->schema()->indexExists('entity_test_update', 'entity_test_update__new_index'), 'Index deleted.');
  }

  /**
   * Tests creating a multi-field index when there are existing entities.
   */
  public function testEntityIndexCreateWithData() {
    // Save an entity.
    $name = $this->randomString();
    $entity = $this->entityManager->getStorage('entity_test_update')->create(array('name' => $name));
    $entity->save();

    // Add an entity index, run the update. For now, it's expected to throw an
    // exception.
    // @todo Improve SqlContentEntityStorageSchema::requiresEntityDataMigration()
    //   to return FALSE when only index changes are required, so that it can be
    //   applied on top of existing data: https://www.drupal.org/node/2340993.
    $this->addEntityIndex();
    try {
      $this->entityDefinitionUpdateManager->applyUpdates();
      $this->fail('EntityStorageException thrown when trying to apply an update that requires data migration.');
    }
    catch (EntityStorageException $e) {
      $this->pass('EntityStorageException thrown when trying to apply an update that requires data migration.');
    }
  }

  /**
   * Updates the 'entity_test_update' entity type to revisionable.
   */
  protected function updateEntityTypeToRevisionable() {
    $entity_type = clone $this->entityManager->getDefinition('entity_test_update');
    $keys = $entity_type->getKeys();
    $keys['revision'] = 'revision_id';
    $entity_type->set('entity_keys', $keys);
    $this->state->set('entity_test_update.entity_type', $entity_type);
    $this->entityManager->clearCachedDefinitions();
  }

  /**
   * Adds a new base field to the 'entity_test_update' entity type.
   *
   * @param string $type
   *   (optional) The field type for the new field. Defaults to 'string'.
   */
  protected function addBaseField($type = 'string') {
    $definitions['new_base_field'] = BaseFieldDefinition::create($type)
      ->setName('new_base_field')
      ->setLabel(t('A new base field'));
    $this->state->set('entity_test_update.additional_base_field_definitions', $definitions);
    $this->entityManager->clearCachedDefinitions();
  }

  /**
   * Modifies the new base field from 'string' to 'text'.
   */
  protected function modifyBaseField() {
    $this->addBaseField('text');
  }

  /**
   * Removes the new base field from the 'entity_test_update' entity type.
   */
  protected function removeBaseField() {
    $this->state->delete('entity_test_update.additional_base_field_definitions');
    $this->entityManager->clearCachedDefinitions();
  }

  /**
   * Adds a single-field index to the base field.
   */
  protected function addBaseFieldIndex() {
    $this->state->set('entity_test_update.additional_field_index.entity_test_update.new_base_field', TRUE);
    $this->entityManager->clearCachedDefinitions();
  }

  /**
   * Removes the index added in addBaseFieldIndex().
   */
  protected function removeBaseFieldIndex() {
    $this->state->delete('entity_test_update.additional_field_index.entity_test_update.new_base_field');
    $this->entityManager->clearCachedDefinitions();
  }

  /**
   * Adds a new bundle field to the 'entity_test_update' entity type.
   *
   * @param string $type
   *   (optional) The field type for the new field. Defaults to 'string'.
   */
  protected function addBundleField($type = 'string') {
    $definitions['new_bundle_field'] = FieldStorageDefinition::create($type)
      ->setName('new_bundle_field')
      ->setLabel(t('A new bundle field'))
      ->setTargetEntityTypeId('entity_test_update');
    $this->state->set('entity_test_update.additional_field_storage_definitions', $definitions);
    $this->state->set('entity_test_update.additional_bundle_field_definitions.test_bundle', $definitions);
    $this->entityManager->clearCachedDefinitions();
  }

  /**
   * Modifies the new bundle field from 'string' to 'text'.
   */
  protected function modifyBundleField() {
    $this->addBundleField('text');
  }

  /**
   * Removes the new bundle field from the 'entity_test_update' entity type.
   */
  protected function removeBundleField() {
    $this->state->delete('entity_test_update.additional_field_storage_definitions');
    $this->state->delete('entity_test_update.additional_bundle_field_definitions.test_bundle');
    $this->entityManager->clearCachedDefinitions();
  }

  /**
   * Adds an index to the 'entity_test_update' entity type's base table.
   *
   * @see \Drupal\entity_test\EntityTestStorageSchema::getEntitySchema().
   */
  protected function addEntityIndex() {
    $indexes = array(
      'entity_test_update__new_index' => array('name', 'user_id'),
    );
    $this->state->set('entity_test_update.additional_entity_indexes', $indexes);
  }

  /**
   * Removes the index added in addEntityIndex().
   */
  protected function removeEntityIndex() {
    $this->state->delete('entity_test_update.additional_entity_indexes');
  }

}
