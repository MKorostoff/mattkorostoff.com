<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\Sql\SqlContentEntityStorageSchemaTest.
 */

namespace Drupal\Tests\Core\Entity\Sql;

use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\Sql\DefaultTableMapping;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema
 * @group Entity
 */
class SqlContentEntityStorageSchemaTest extends UnitTestCase {

  /**
   * The mocked entity manager used in this test.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The mocked entity type used in this test.
   *
   * @var \Drupal\Core\Entity\ContentEntityTypeInterface
   */
  protected $entityType;

  /**
   * The mocked SQL storage used in this test.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $storage;

  /**
   * The mocked field definitions used in this test.
   *
   * @var \Drupal\Core\Field\FieldStorageDefinitionInterface[]|\PHPUnit_Framework_MockObject_MockObject[]
   */
  protected $storageDefinitions;

  /**
   * The storage schema handler used in this test.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema.
   */
  protected $storageSchema;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->entityManager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $this->storage = $this->getMockBuilder('Drupal\Core\Entity\Sql\SqlContentEntityStorage')
      ->disableOriginalConstructor()
      ->getMock();

    $this->storage->expects($this->any())
      ->method('getBaseTable')
      ->will($this->returnValue('entity_test'));

    // Add an ID field. This also acts as a test for a simple, single-column
    // field.
    $this->setUpStorageDefinition('id', array(
      'columns' => array(
        'value' => array(
          'type' => 'int',
        ),
      ),
    ));
  }

  /**
   * Tests the schema for non-revisionable, non-translatable entities.
   *
   * @covers ::__construct()
   * @covers ::getSchema()
   * @covers ::getEntitySchemaTables()
   * @covers ::initializeBaseTable()
   * @covers ::addTableDefaults()
   * @covers ::getEntityIndexName()
   * @covers ::addFieldSchema()
   * @covers ::getFieldIndexes()
   * @covers ::getFieldUniqueKeys()
   * @covers ::getFieldForeignKeys()
   * @covers ::getFieldSchemaData()
   * @covers ::addDefaultLangcodeSchema()
   * @covers ::processBaseTable()
   * @covers ::processIdentifierSchema()
   */
  public function testGetSchemaBase() {
    $this->entityType = new ContentEntityType(array(
      'id' => 'entity_test',
      'entity_keys' => array('id' => 'id'),
    ));

    // Add a field with a 'length' constraint.
    $this->setUpStorageDefinition('name', array(
      'columns' => array(
        'value' => array(
          'type' => 'varchar',
          'length' => 255,
        ),
      ),
    ));
    // Add a multi-column field.
    $this->setUpStorageDefinition('description', array(
      'columns' => array(
        'value' => array(
          'type' => 'text',
          'description' => 'The text value',
        ),
        'format' => array(
          'type' => 'varchar',
          'description' => 'The text description',
        ),
      ),
    ));
    // Add a field with a unique key.
    $this->setUpStorageDefinition('uuid', array(
      'columns' => array(
        'value' => array(
          'type' => 'varchar',
          'length' => 128,
        ),
      ),
      'unique keys' => array(
        'value' => array('value'),
      ),
    ));
    // Add a field with a unique key, specified as column name and length.
    $this->setUpStorageDefinition('hash', array(
      'columns' => array(
        'value' => array(
          'type' => 'varchar',
          'length' => 20,
        ),
      ),
      'unique keys' => array(
        'value' => array(array('value', 10)),
      ),
    ));
    // Add a field with a multi-column unique key.
    $this->setUpStorageDefinition('email', array(
      'columns' => array(
        'username' => array(
          'type' => 'varchar',
        ),
        'hostname' => array(
          'type' => 'varchar',
        ),
        'domain' => array(
          'type' => 'varchar',
        )
      ),
      'unique keys' => array(
        'email' => array('username', 'hostname', array('domain', 3)),
      ),
    ));
    // Add a field with an index.
    $this->setUpStorageDefinition('owner', array(
      'columns' => array(
        'target_id' => array(
          'type' => 'int',
        ),
      ),
      'indexes' => array(
        'target_id' => array('target_id'),
      ),
    ));
    // Add a field with an index, specified as column name and length.
    $this->setUpStorageDefinition('translator', array(
      'columns' => array(
        'target_id' => array(
          'type' => 'int',
        ),
      ),
      'indexes' => array(
        'target_id' => array(array('target_id', 10)),
      ),
    ));
    // Add a field with a multi-column index.
    $this->setUpStorageDefinition('location', array(
      'columns' => array(
        'country' => array(
          'type' => 'varchar',
        ),
        'state' => array(
          'type' => 'varchar',
        ),
        'city' => array(
          'type' => 'varchar',
        )
      ),
      'indexes' => array(
        'country_state_city' => array('country', 'state', array('city', 10)),
      ),
    ));
    // Add a field with a foreign key.
    $this->setUpStorageDefinition('editor', array(
      'columns' => array(
        'target_id' => array(
          'type' => 'int',
        ),
      ),
      'foreign keys' => array(
        'user_id' => array(
          'table' => 'users',
          'columns' => array('target_id' => 'uid'),
        ),
      ),
    ));
    // Add a multi-column field with a foreign key.
    $this->setUpStorageDefinition('editor_revision', array(
      'columns' => array(
        'target_id' => array(
          'type' => 'int',
        ),
        'target_revision_id' => array(
          'type' => 'int',
        ),
      ),
      'foreign keys' => array(
        'user_id' => array(
          'table' => 'users',
          'columns' => array('target_id' => 'uid'),
        ),
      ),
    ));
    // Add a field with a really long index.
    $this->setUpStorageDefinition('long_index_name', array(
      'columns' => array(
        'long_index_name' => array(
          'type' => 'int',
        ),
      ),
      'indexes' => array(
        'long_index_name_really_long_long_name' => array(array('long_index_name', 10)),
      ),
    ));

    $expected = array(
      'entity_test' => array(
        'description' => 'The base table for entity_test entities.',
        'fields' => array(
          'id' => array(
            'description' => 'The id field.',
            'type' => 'serial',
            'not null' => TRUE,
          ),
          'name' => array(
            'description' => 'The name field.',
            'type' => 'varchar',
            'length' => 255,
          ),
          'description__value' => array(
            'description' => 'The description field.',
            'type' => 'text',
          ),
          'description__format' => array(
            'description' => 'The description field.',
            'type' => 'varchar',
          ),
          'uuid' => array(
            'description' => 'The uuid field.',
            'type' => 'varchar',
            'length' => 128,
          ),
          'hash' => array(
            'description' => 'The hash field.',
            'type' => 'varchar',
            'length' => 20,
          ),
          'email__username' => array(
            'description' => 'The email field.',
            'type' => 'varchar',
          ),
          'email__hostname' => array(
            'description' => 'The email field.',
            'type' => 'varchar',
          ),
          'email__domain' => array(
            'description' => 'The email field.',
            'type' => 'varchar',
          ),
          'owner' => array(
            'description' => 'The owner field.',
            'type' => 'int',
          ),
          'translator' => array(
            'description' => 'The translator field.',
            'type' => 'int',
          ),
          'location__country' => array(
            'description' => 'The location field.',
            'type' => 'varchar',
          ),
          'location__state' => array(
            'description' => 'The location field.',
            'type' => 'varchar',
          ),
          'location__city' => array(
            'description' => 'The location field.',
            'type' => 'varchar',
          ),
          'editor' => array(
            'description' => 'The editor field.',
            'type' => 'int',
          ),
          'editor_revision__target_id' => array(
            'description' => 'The editor_revision field.',
            'type' => 'int',
          ),
          'editor_revision__target_revision_id' => array(
            'description' => 'The editor_revision field.',
            'type' => 'int',
          ),
          'long_index_name' => array(
            'description' => 'The long_index_name field.',
            'type' => 'int',
          ),
          'default_langcode' => array(
            'description' => 'Boolean indicating whether field values are in the default entity language.',
            'type' => 'int',
            'size' => 'tiny',
            'not null' => TRUE,
            'default' => 1,
          ),
        ),
        'primary key' => array('id'),
        'unique keys' => array(
          'entity_test_field__uuid__value' => array('uuid'),
          'entity_test_field__hash__value' => array(array('hash', 10)),
          'entity_test_field__email__email' => array(
            'email__username',
            'email__hostname',
            array('email__domain', 3),
          ),
        ),
        'indexes' => array(
          'entity_test_field__owner__target_id' => array('owner'),
          'entity_test_field__translator__target_id' => array(
            array('translator', 10),
          ),
          'entity_test_field__location__country_state_city' => array(
            'location__country',
            'location__state',
            array('location__city', 10),
          ),
          'entity_test__b588603cb9' => array(
            array('long_index_name', 10),
          ),

        ),
        'foreign keys' => array(
          'entity_test_field__editor__user_id' => array(
            'table' => 'users',
            'columns' => array('editor' => 'uid'),
          ),
          'entity_test_field__editor_revision__user_id' => array(
            'table' => 'users',
            'columns' => array('editor_revision__target_id' => 'uid'),
          ),
        ),
      ),
    );

    $this->setUpStorageSchema($expected);

    $table_mapping = new DefaultTableMapping($this->storageDefinitions);
    $table_mapping->setFieldNames('entity_test', array_keys($this->storageDefinitions));
    $table_mapping->setExtraColumns('entity_test', array('default_langcode'));

    $this->storage->expects($this->any())
      ->method('getTableMapping')
      ->will($this->returnValue($table_mapping));

    $this->storageSchema->onEntityTypeCreate($this->entityType);
  }

  /**
   * Tests the schema for revisionable, non-translatable entities.
   *
   * @covers ::__construct()
   * @covers ::getSchema()
   * @covers ::getEntitySchemaTables()
   * @covers ::initializeBaseTable()
   * @covers ::initializeRevisionTable()
   * @covers ::addTableDefaults()
   * @covers ::getEntityIndexName()
   * @covers ::processRevisionTable()
   * @covers ::processIdentifierSchema()
   */
  public function testGetSchemaRevisionable() {
    $this->entityType = new ContentEntityType(array(
      'id' => 'entity_test',
      'entity_keys' => array(
        'id' => 'id',
        'revision' => 'revision_id',
      ),
    ));

    $this->storage->expects($this->exactly(2))
      ->method('getRevisionTable')
      ->will($this->returnValue('entity_test_revision'));

    $this->setUpStorageDefinition('revision_id', array(
      'columns' => array(
        'value' => array(
          'type' => 'int',
        ),
      ),
    ));

    $expected = array(
      'entity_test' => array(
        'description' => 'The base table for entity_test entities.',
        'fields' => array(
          'id' => array(
            'description' => 'The id field.',
            'type' => 'serial',
            'not null' => TRUE,
          ),
          'revision_id' => array(
            'description' => 'The revision_id field.',
            'type' => 'int',
          )
        ),
        'primary key' => array('id'),
        'unique keys' => array(
          'entity_test__revision_id' => array('revision_id'),
        ),
        'indexes' => array(),
        'foreign keys' => array(
          'entity_test__revision' => array(
            'table' => 'entity_test_revision',
            'columns' => array('revision_id' => 'revision_id'),
          )
        ),
      ),
      'entity_test_revision' => array(
        'description' => 'The revision table for entity_test entities.',
        'fields' => array(
          'id' => array(
            'description' => 'The id field.',
            'type' => 'int',
            'not null' => TRUE,
          ),
          'revision_id' => array(
            'description' => 'The revision_id field.',
            'type' => 'serial',
          ),
        ),
        'primary key' => array('revision_id'),
        'unique keys' => array(),
        'indexes' => array(
          'entity_test__id' => array('id'),
        ),
        'foreign keys' => array(
          'entity_test__revisioned' => array(
            'table' => 'entity_test',
            'columns' => array('id' => 'id'),
          ),
        ),
      ),
    );

    $this->setUpStorageSchema($expected);

    $table_mapping = new DefaultTableMapping($this->storageDefinitions);
    $table_mapping->setFieldNames('entity_test', array_keys($this->storageDefinitions));
    $table_mapping->setFieldNames('entity_test_revision', array_keys($this->storageDefinitions));

    $this->storage->expects($this->any())
      ->method('getTableMapping')
      ->will($this->returnValue($table_mapping));

    $this->storageSchema->onEntityTypeCreate($this->entityType);
  }

  /**
   * Tests the schema for non-revisionable, translatable entities.
   *
   * @covers ::__construct()
   * @covers ::getSchema()
   * @covers ::getEntitySchemaTables()
   * @covers ::initializeDataTable()
   * @covers ::addTableDefaults()
   * @covers ::getEntityIndexName()
   * @covers ::processDataTable()
   */
  public function testGetSchemaTranslatable() {
    $this->entityType = new ContentEntityType(array(
      'id' => 'entity_test',
      'entity_keys' => array(
        'id' => 'id',
      ),
    ));

    $this->storage->expects($this->any())
      ->method('getDataTable')
      ->will($this->returnValue('entity_test_field_data'));

    $this->setUpStorageDefinition('langcode', array(
      'columns' => array(
        'value' => array(
          'type' => 'varchar',
        ),
      ),
    ));

    $expected = array(
      'entity_test' => array(
        'description' => 'The base table for entity_test entities.',
        'fields' => array(
          'id' => array(
            'description' => 'The id field.',
            'type' => 'serial',
            'not null' => TRUE,
          ),
          'langcode' => array(
            'description' => 'The langcode field.',
            'type' => 'varchar',
            'not null' => TRUE,
          )
        ),
        'primary key' => array('id'),
        'unique keys' => array(),
        'indexes' => array(),
        'foreign keys' => array(),
      ),
      'entity_test_field_data' => array(
        'description' => 'The data table for entity_test entities.',
        'fields' => array(
          'id' => array(
            'description' => 'The id field.',
            'type' => 'int',
            'not null' => TRUE,
          ),
          'langcode' => array(
            'description' => 'The langcode field.',
            'type' => 'varchar',
            'not null' => TRUE,
          ),
        ),
        'primary key' => array('id', 'langcode'),
        'unique keys' => array(),
        'indexes' => array(),
        'foreign keys' => array(
          'entity_test' => array(
            'table' => 'entity_test',
            'columns' => array('id' => 'id'),
          ),
        ),
      ),
    );

    $this->setUpStorageSchema($expected);

    $table_mapping = new DefaultTableMapping($this->storageDefinitions);
    $table_mapping->setFieldNames('entity_test', array_keys($this->storageDefinitions));
    $table_mapping->setFieldNames('entity_test_field_data', array_keys($this->storageDefinitions));

    $this->storage->expects($this->any())
      ->method('getTableMapping')
      ->will($this->returnValue($table_mapping));

    $this->storageSchema->onEntityTypeCreate($this->entityType);
  }

  /**
   * Tests the schema for revisionable, translatable entities.
   *
   * @covers ::__construct()
   * @covers ::getSchema()
   * @covers ::getEntitySchemaTables()
   * @covers ::initializeDataTable()
   * @covers ::addTableDefaults()
   * @covers ::getEntityIndexName()
   * @covers ::initializeRevisionDataTable()
   * @covers ::processRevisionDataTable()
   */
  public function testGetSchemaRevisionableTranslatable() {
    $this->entityType = new ContentEntityType(array(
      'id' => 'entity_test',
      'entity_keys' => array(
        'id' => 'id',
        'revision' => 'revision_id',
      ),
    ));

    $this->storage->expects($this->exactly(3))
      ->method('getRevisionTable')
      ->will($this->returnValue('entity_test_revision'));
    $this->storage->expects($this->once())
      ->method('getDataTable')
      ->will($this->returnValue('entity_test_field_data'));
    $this->storage->expects($this->once())
      ->method('getRevisionDataTable')
      ->will($this->returnValue('entity_test_revision_field_data'));

    $this->setUpStorageDefinition('revision_id', array(
      'columns' => array(
        'value' => array(
          'type' => 'int',
        ),
      ),
    ));
    $this->setUpStorageDefinition('langcode', array(
      'columns' => array(
        'value' => array(
          'type' => 'varchar',
        ),
      ),
    ));

    $expected = array(
      'entity_test' => array(
        'description' => 'The base table for entity_test entities.',
        'fields' => array(
          'id' => array(
            'description' => 'The id field.',
            'type' => 'serial',
            'not null' => TRUE,
          ),
          'revision_id' => array(
            'description' => 'The revision_id field.',
            'type' => 'int',
          ),
          'langcode' => array(
            'description' => 'The langcode field.',
            'type' => 'varchar',
            'not null' => TRUE,
          )
        ),
        'primary key' => array('id'),
        'unique keys' => array(
          'entity_test__revision_id' => array('revision_id'),
        ),
        'indexes' => array(),
        'foreign keys' => array(
          'entity_test__revision' => array(
            'table' => 'entity_test_revision',
            'columns' => array('revision_id' => 'revision_id'),
          ),
        ),
      ),
      'entity_test_revision' => array(
        'description' => 'The revision table for entity_test entities.',
        'fields' => array(
          'id' => array(
            'description' => 'The id field.',
            'type' => 'int',
            'not null' => TRUE,
          ),
          'revision_id' => array(
            'description' => 'The revision_id field.',
            'type' => 'serial',
          ),
          'langcode' => array(
            'description' => 'The langcode field.',
            'type' => 'varchar',
            'not null' => TRUE,
          ),
        ),
        'primary key' => array('revision_id'),
        'unique keys' => array(),
        'indexes' => array(
          'entity_test__id' => array('id'),
        ),
        'foreign keys' => array(
          'entity_test__revisioned' => array(
            'table' => 'entity_test',
            'columns' => array('id' => 'id'),
          ),
        ),
      ),
      'entity_test_field_data' => array(
        'description' => 'The data table for entity_test entities.',
        'fields' => array(
          'id' => array(
            'description' => 'The id field.',
            'type' => 'int',
            'not null' => TRUE,
          ),
          'revision_id' => array(
            'description' => 'The revision_id field.',
            'type' => 'int',
          ),
          'langcode' => array(
            'description' => 'The langcode field.',
            'type' => 'varchar',
            'not null' => TRUE,
          ),
        ),
        'primary key' => array('id', 'langcode'),
        'unique keys' => array(),
        'indexes' => array(
          'entity_test__revision_id' => array('revision_id'),
        ),
        'foreign keys' => array(
          'entity_test' => array(
            'table' => 'entity_test',
            'columns' => array('id' => 'id'),
          ),
        ),
      ),
      'entity_test_revision_field_data' => array(
        'description' => 'The revision data table for entity_test entities.',
        'fields' => array(
          'id' => array(
            'description' => 'The id field.',
            'type' => 'int',
            'not null' => TRUE,
          ),
          'revision_id' => array(
            'description' => 'The revision_id field.',
            'type' => 'int',
          ),
          'langcode' => array(
            'description' => 'The langcode field.',
            'type' => 'varchar',
            'not null' => TRUE,
          ),
        ),
        'primary key' => array('revision_id', 'langcode'),
        'unique keys' => array(),
        'indexes' => array(),
        'foreign keys' => array(
          'entity_test' => array(
            'table' => 'entity_test',
            'columns' => array('id' => 'id'),
          ),
          'entity_test__revision' => array(
            'table' => 'entity_test_revision',
            'columns' => array('revision_id' => 'revision_id'),
          ),
        ),
      ),
    );

    $this->setUpStorageSchema($expected);

    $table_mapping = new DefaultTableMapping($this->storageDefinitions);
    $table_mapping->setFieldNames('entity_test', array_keys($this->storageDefinitions));
    $table_mapping->setFieldNames('entity_test_revision', array_keys($this->storageDefinitions));
    $table_mapping->setFieldNames('entity_test_field_data', array_keys($this->storageDefinitions));
    $table_mapping->setFieldNames('entity_test_revision_field_data', array_keys($this->storageDefinitions));

    $this->storage->expects($this->any())
      ->method('getTableMapping')
      ->will($this->returnValue($table_mapping));

    $this->storageSchema->onEntityTypeCreate($this->entityType);
  }

  /**
   * Tests the schema for a field dedicated table.
   *
   * @covers ::getDedicatedTableSchema()
   * @covers ::createDedicatedTableSchema()
   */
  public function testDedicatedTableSchema() {
    $entity_type_id = 'entity_test';
    $this->entityType = new ContentEntityType(array(
      'id' => 'entity_test',
      'entity_keys' => array('id' => 'id'),
    ));

    // Setup a field having a dedicated schema.
    $field_name = $this->getRandomGenerator()->name();
    $this->setUpStorageDefinition($field_name, array(
      'columns' => array(
        'shape' => array(
          'type' => 'varchar',
          'length' => 32,
          'not null' => FALSE,
        ),
        'color' => array(
          'type' => 'varchar',
          'length' => 32,
          'not null' => FALSE,
        ),
      ),
      'foreign keys' => array(
        'color' => array(
          'table' => 'color',
          'columns' => array(
            'color' => 'id'
          ),
        ),
      ),
      'unique keys' => array(),
      'indexes' => array(),
    ));

    $field_storage = $this->storageDefinitions[$field_name];
    $field_storage
      ->expects($this->any())
      ->method('getType')
      ->will($this->returnValue('shape'));
    $field_storage
      ->expects($this->any())
      ->method('getTargetEntityTypeId')
      ->will($this->returnValue($entity_type_id));
    $field_storage
      ->expects($this->any())
      ->method('isMultiple')
      ->will($this->returnValue(TRUE));

    $this->storageDefinitions['id']
      ->expects($this->any())
      ->method('getType')
      ->will($this->returnValue('integer'));

    $expected = array(
      $entity_type_id . '__' . $field_name => array(
        'description' => "Data storage for $entity_type_id field $field_name.",
        'fields' => array(
          'bundle' => array(
            'type' => 'varchar',
            'length' => 128,
            'not null' => true,
            'default' => '',
            'description' => 'The field instance bundle to which this row belongs, used when deleting a field instance',
          ),
          'deleted' => array(
            'type' => 'int',
            'size' => 'tiny',
            'not null' => true,
            'default' => 0,
            'description' => 'A boolean indicating whether this data item has been deleted',
          ),
          'entity_id' => array(
            'type' => 'int',
            'unsigned' => true,
            'not null' => true,
            'description' => 'The entity id this data is attached to',
          ),
          'revision_id' => array(
            'type' => 'int',
            'unsigned' => true,
            'not null' => true,
            'description' => 'The entity revision id this data is attached to, which for an unversioned entity type is the same as the entity id',
          ),
          'langcode' => array(
            'type' => 'varchar',
            'length' => 32,
            'not null' => true,
            'default' => '',
            'description' => 'The language code for this data item.',
          ),
          'delta' => array(
            'type' => 'int',
            'unsigned' => true,
            'not null' => true,
            'description' => 'The sequence number for this data item, used for multi-value fields',
          ),
          $field_name . '_shape' => array(
            'type' => 'varchar',
            'length' => 32,
            'not null' => false,
          ),
          $field_name . '_color' => array(
            'type' => 'varchar',
            'length' => 32,
            'not null' => false,
          ),
        ),
        'primary key' => array('entity_id', 'deleted', 'delta', 'langcode'),
        'indexes' => array(
          'bundle' => array('bundle'),
          'deleted' => array('deleted'),
          'entity_id' => array('entity_id'),
          'revision_id' => array('revision_id'),
          'langcode' => array('langcode'),
        ),
        'foreign keys' => array(
          $field_name . '_color' => array(
            'table' => 'color',
            'columns' => array(
              $field_name . '_color' => 'id',
            ),
          ),
        ),
      ),
    );

    $this->setUpStorageSchema($expected);

    $table_mapping = new DefaultTableMapping($this->storageDefinitions);
    $table_mapping->setFieldNames($entity_type_id, array_keys($this->storageDefinitions));
    $table_mapping->setExtraColumns($entity_type_id, array('default_langcode'));

    $this->storage->expects($this->any())
      ->method('getTableMapping')
      ->will($this->returnValue($table_mapping));

    $this->storageSchema->onFieldStorageDefinitionCreate($field_storage);
  }

  /**
   * Tests the schema for a field dedicated table for an entity with a string identifier.
   *
   * @covers ::getDedicatedTableSchema()
   * @covers ::createDedicatedTableSchema()
   */
  public function testDedicatedTableSchemaForEntityWithStringIdentifier() {
    $entity_type_id = 'entity_test';
    $this->entityType = new ContentEntityType(array(
      'id' => 'entity_test',
      'entity_keys' => array('id' => 'id'),
    ));

    // Setup a field having a dedicated schema.
    $field_name = $this->getRandomGenerator()->name();
    $this->setUpStorageDefinition($field_name, array(
      'columns' => array(
        'shape' => array(
          'type' => 'varchar',
          'length' => 32,
          'not null' => FALSE,
        ),
        'color' => array(
          'type' => 'varchar',
          'length' => 32,
          'not null' => FALSE,
        ),
      ),
      'foreign keys' => array(
        'color' => array(
          'table' => 'color',
          'columns' => array(
            'color' => 'id'
          ),
        ),
      ),
      'unique keys' => array(),
      'indexes' => array(),
    ));

    $field_storage = $this->storageDefinitions[$field_name];
    $field_storage
      ->expects($this->any())
      ->method('getType')
      ->will($this->returnValue('shape'));
    $field_storage
      ->expects($this->any())
      ->method('getTargetEntityTypeId')
      ->will($this->returnValue($entity_type_id));
    $field_storage
      ->expects($this->any())
      ->method('isMultiple')
      ->will($this->returnValue(TRUE));

    $this->storageDefinitions['id']
      ->expects($this->any())
      ->method('getType')
      ->will($this->returnValue('string'));

    $expected = array(
      $entity_type_id . '__' . $field_name => array(
        'description' => "Data storage for $entity_type_id field $field_name.",
        'fields' => array(
          'bundle' => array(
            'type' => 'varchar',
            'length' => 128,
            'not null' => true,
            'default' => '',
            'description' => 'The field instance bundle to which this row belongs, used when deleting a field instance',
          ),
          'deleted' => array(
            'type' => 'int',
            'size' => 'tiny',
            'not null' => true,
            'default' => 0,
            'description' => 'A boolean indicating whether this data item has been deleted',
          ),
          'entity_id' => array(
            'type' => 'varchar',
            'length' => 128,
            'not null' => true,
            'description' => 'The entity id this data is attached to',
          ),
          'revision_id' => array(
            'type' => 'varchar',
            'length' => 128,
            'not null' => true,
            'description' => 'The entity revision id this data is attached to, which for an unversioned entity type is the same as the entity id',
          ),
          'langcode' => array(
            'type' => 'varchar',
            'length' => 32,
            'not null' => true,
            'default' => '',
            'description' => 'The language code for this data item.',
          ),
          'delta' => array(
            'type' => 'int',
            'unsigned' => true,
            'not null' => true,
            'description' => 'The sequence number for this data item, used for multi-value fields',
          ),
          $field_name . '_shape' => array(
            'type' => 'varchar',
            'length' => 32,
            'not null' => false,
          ),
          $field_name . '_color' => array(
            'type' => 'varchar',
            'length' => 32,
            'not null' => false,
          ),
        ),
        'primary key' => array('entity_id', 'deleted', 'delta', 'langcode'),
        'indexes' => array(
          'bundle' => array('bundle'),
          'deleted' => array('deleted'),
          'entity_id' => array('entity_id'),
          'revision_id' => array('revision_id'),
          'langcode' => array('langcode'),
        ),
        'foreign keys' => array(
          $field_name . '_color' => array(
            'table' => 'color',
            'columns' => array(
              $field_name . '_color' => 'id',
            ),
          ),
        ),
      ),
    );

    $this->setUpStorageSchema($expected);

    $table_mapping = new DefaultTableMapping($this->storageDefinitions);
    $table_mapping->setFieldNames($entity_type_id, array_keys($this->storageDefinitions));
    $table_mapping->setExtraColumns($entity_type_id, array('default_langcode'));

    $this->storage->expects($this->any())
      ->method('getTableMapping')
      ->will($this->returnValue($table_mapping));

    $this->storageSchema->onFieldStorageDefinitionCreate($field_storage);
  }

  /**
   * Sets up the storage schema object to test.
   *
   * This uses the field definitions set in $this->storageDefinitions.
   *
   * @param array $expected
   *   (optional) An associative array describing the expected entity schema to
   *   be created. Defaults to expecting nothing.
   */
  protected function setUpStorageSchema(array $expected = array()) {
    $this->entityManager->expects($this->any())
      ->method('getDefinition')
      ->with($this->entityType->id())
      ->will($this->returnValue($this->entityType));

    $this->entityManager->expects($this->any())
      ->method('getFieldStorageDefinitions')
      ->with($this->entityType->id())
      ->will($this->returnValue($this->storageDefinitions));

    $db_schema_handler = $this->getMockBuilder('Drupal\Core\Database\Schema')
      ->disableOriginalConstructor()
      ->getMock();

    if ($expected) {
      $invocation_count = 0;
      $expected_table_names = array_keys($expected);
      $expected_table_schemas = array_values($expected);

      $db_schema_handler->expects($this->any())
        ->method('createTable')
        ->with(
          $this->callback(function($table_name) use (&$invocation_count, $expected_table_names) {
            return $expected_table_names[$invocation_count] == $table_name;
          }),
          $this->callback(function($table_schema) use (&$invocation_count, $expected_table_schemas) {
            return $expected_table_schemas[$invocation_count] == $table_schema;
          })
        )
        ->will($this->returnCallback(function() use (&$invocation_count) {
          $invocation_count++;
        }));
    }

    $connection = $this->getMockBuilder('Drupal\Core\Database\Connection')
      ->disableOriginalConstructor()
      ->getMock();
    $connection->expects($this->any())
      ->method('schema')
      ->will($this->returnValue($db_schema_handler));

    $key_value = $this->getMock('Drupal\Core\KeyValueStore\KeyValueStoreInterface');
    $this->storageSchema = $this->getMockBuilder('Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema')
      ->setConstructorArgs(array($this->entityManager, $this->entityType, $this->storage, $connection))
      ->setMethods(array('installedStorageSchema'))
      ->getMock();
    $this->storageSchema
      ->expects($this->any())
      ->method('installedStorageSchema')
      ->will($this->returnValue($key_value));
  }

  /**
   * Sets up a field definition.
   *
   * @param string $field_name
   *   The field name.
   * @param array $schema
   *   The schema array of the field definition, as returned from
   *   FieldStorageDefinitionInterface::getSchema().
   */
  public function setUpStorageDefinition($field_name, array $schema) {
    $this->storageDefinitions[$field_name] = $this->getMock('Drupal\Tests\Core\Field\TestBaseFieldDefinitionInterface');
    $this->storageDefinitions[$field_name]->expects($this->any())
      ->method('isBaseField')
      ->will($this->returnValue(TRUE));
    // getName() is called once for each table.
    $this->storageDefinitions[$field_name]->expects($this->any())
      ->method('getName')
      ->will($this->returnValue($field_name));
    // getDescription() is called once for each table.
    $this->storageDefinitions[$field_name]->expects($this->any())
      ->method('getDescription')
      ->will($this->returnValue("The $field_name field."));
    // getSchema() is called once for each table.
    $this->storageDefinitions[$field_name]->expects($this->any())
      ->method('getSchema')
      ->will($this->returnValue($schema));
    $this->storageDefinitions[$field_name]->expects($this->any())
      ->method('getColumns')
      ->will($this->returnValue($schema['columns']));
  }

}
