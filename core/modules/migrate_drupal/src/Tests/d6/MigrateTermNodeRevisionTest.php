<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateTermNodeTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;

/**
 * Upgrade taxonomy term node associations.
 *
 * @group migrate_drupal
 */
class MigrateTermNodeRevisionTest extends MigrateTermNodeTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $id_mappings = array(
      'd6_term_node' => array(
        array(array(2), array(1)),
      ),
      'd6_node_revision' => array(
        array(array(2), array(2)),
      ),
    );
    $this->prepareMigrations($id_mappings);
    /** @var \Drupal\migrate\entity\Migration $migration */
    $migrations = entity_load_multiple('migration', array('d6_term_node_revision:*'));
    foreach ($migrations as $migration) {
      $executable = new MigrateExecutable($migration, $this);
      $executable->import();
    }
  }

  /**
   * Tests the Drupal 6 term-node revision association to Drupal 8 migration.
   */
  public function testTermRevisionNode() {
    $node = \Drupal::entityManager()->getStorage('node')->loadRevision(2);
    $this->assertEqual(count($node->vocabulary_3_i_2_), 2);
    $this->assertEqual($node->vocabulary_3_i_2_[0]->target_id, 4);
    $this->assertEqual($node->vocabulary_3_i_2_[1]->target_id, 5);
  }

}
