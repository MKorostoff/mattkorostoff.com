<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateCommentTypeTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Upgrade comment type.
 *
 * @group migrate_drupal
 */
class MigrateCommentTypeTest extends MigrateDrupalTestBase {

  static $modules = array('node', 'comment');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    /** @var \Drupal\migrate\entity\Migration $migration */
    $migration = entity_load('migration', 'd6_comment_type');

    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6CommentVariable.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests the Drupal 6 to Drupal 8 comment type migration.
   */
  public function testCommentType() {
    $comment_type = entity_load('comment_type', 'comment');
    $this->assertEqual('node', $comment_type->getTargetEntityTypeId());
    $comment_type = entity_load('comment_type', 'comment_no_subject');
    $this->assertEqual('node', $comment_type->getTargetEntityTypeId());
  }

}
