<?php

/**
 * @file
 * Contains \Drupal\aggregator\Tests\FeedCacheTagsTest.
 */

namespace Drupal\aggregator\Tests;

use Drupal\aggregator\Entity\Feed;
use Drupal\system\Tests\Entity\EntityWithUriCacheTagsTestBase;

/**
 * Tests the Feed entity's cache tags.
 */
class FeedCacheTagsTest extends EntityWithUriCacheTagsTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('aggregator');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return parent::generateStandardizedInfo('Aggregator feed', 'Aggregator');
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Give anonymous users permission to access feeds, so that we can verify
    // the cache tags of cached versions of feeds.
    $user_role = entity_load('user_role', DRUPAL_ANONYMOUS_RID);
    $user_role->grantPermission('access news feeds');
    $user_role->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    // Create a "Llama" feed.
    $feed = Feed::create(array(
      'title' => 'Llama',
      'url' => 'https://www.drupal.org/',
      'refresh' => 900,
      'checked' => 1389919932,
      'description' => 'Drupal.org',
    ));
    $feed->save();

    return $feed;
  }

}
