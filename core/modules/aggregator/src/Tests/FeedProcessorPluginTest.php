<?php

/**
 * @file
 * Contains \Drupal\aggregator\Tests\FeedProcessorPluginTest.
 */

namespace Drupal\aggregator\Tests;

/**
 * Tests the processor plugins functionality and discoverability.
 *
 * @group aggregator
 * @see \Drupal\aggregator_test\Plugin\aggregator\processor\TestProcessor.
 */
class FeedProcessorPluginTest extends AggregatorTestBase {

  /**
   * Overrides \Drupal\simpletest\WebTestBase::setUp().
   */
  protected function setUp() {
    parent::setUp();
    // Enable test plugins.
    $this->enableTestPlugins();
    // Create some nodes.
    $this->createSampleNodes();
  }

  /**
   * Test processing functionality.
   */
  public function testProcess() {
    $feed = $this->createFeed();
    $this->updateFeedItems($feed);
    foreach ($feed->items as $iid) {
      $item = entity_load('aggregator_item', $iid);
      $this->assertTrue(strpos($item->label(), 'testProcessor') === 0);
    }
  }

  /**
   * Test deleting functionality.
   */
  public function testDelete() {
    $feed = $this->createFeed();
    $this->updateAndDelete($feed, NULL);
    // Make sure the feed title is changed.
    $entities = entity_load_multiple_by_properties('aggregator_feed', array('description' => $feed->description->value));
    $this->assertTrue(empty($entities));
  }

  /**
   * Test post-processing functionality.
   */
  public function testPostProcess() {
    $feed = $this->createFeed(NULL, array('refresh' => 1800));
    $this->updateFeedItems($feed);
    // Reload the feed to get new values.
    $feed = entity_load('aggregator_feed', $feed->id(), TRUE);
    // Make sure its refresh rate doubled.
    $this->assertEqual($feed->getRefreshRate(), 3600);
  }
}
