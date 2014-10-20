<?php

/**
 * @file
 * Contains \Drupal\node\Tests\Views\RowPluginTest.
 */

namespace Drupal\node\Tests\Views;

use Drupal\views\Views;

/**
 * Tests the node row plugin.
 *
 * @group node
 * @see \Drupal\node\Plugin\views\row\NodeRow
 */
class RowPluginTest extends NodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'comment');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_node_row_plugin');

  /**
   * Contains all comments keyed by node used by the test.
   *
   * @var array
   */
  protected $comments;

  /**
   * Contains all nodes used by this test.
   *
   * @var array
   */
  protected $nodes;

  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'article'));
    // Create comment field on article.
    $this->container->get('comment.manager')->addDefaultField('node', 'article');

    // Create two nodes, with 5 comments on all of them.
    for ($i = 0; $i < 2; $i++) {
      $this->nodes[] = $this->drupalCreateNode(
        array(
          'type' => 'article',
          'body' => array(
            array(
              'value' => $this->randomMachineName(42),
              'format' => filter_default_format(),
              'summary' => $this->randomMachineName(),
            ),
          ),
        )
      );
    }

    foreach ($this->nodes as $node) {
      for ($i = 0; $i < 5; $i++) {
        $this->comments[$node->id()][] = $this->drupalCreateComment(array('entity_id' => $node->id()));
      }
    }
  }

  /**
   * Helper function to create a random comment.
   *
   * @param array $settings
   *   (optional) An associative array of settings for the comment, as used in
   *   entity_create().
   *
   * @return \Drupal\comment\Entity\Comment
   *   Returns the created and saved comment.
   */
  public function drupalCreateComment(array $settings = array()) {
    $settings += array(
      'subject' => $this->randomMachineName(),
      'entity_id' => $settings['entity_id'],
      'field_name' => 'comment',
      'entity_type' => 'node',
      'comment_body' => $this->randomMachineName(40),
    );

    $comment = entity_create('comment', $settings);
    $comment->save();
    return $comment;
  }

  /**
   * Tests the node row plugin.
   */
  public function testRowPlugin() {
    $view = Views::getView('test_node_row_plugin');
    $view->initDisplay();
    $view->setDisplay('page_1');
    $view->initStyle();
    $view->rowPlugin->options['view_mode'] = 'full';

    // Test with view_mode full.
    $output = $view->preview();
    $output = drupal_render($output);
    foreach ($this->nodes as $node) {
      $this->assertFalse(strpos($output, $node->body->summary) !== FALSE, 'Make sure the teaser appears in the output of the view.');
      $this->assertTrue(strpos($output, $node->body->value) !== FALSE, 'Make sure the full text appears in the output of the view.');
    }

    // Test with teasers.
    $view->rowPlugin->options['view_mode'] = 'teaser';
    $output = $view->preview();
    $output = drupal_render($output);
    foreach ($this->nodes as $node) {
      $this->assertTrue(strpos($output, $node->body->summary) !== FALSE, 'Make sure the teaser appears in the output of the view.');
      $this->assertFalse(strpos($output, $node->body->value) !== FALSE, 'Make sure the full text does not appears in the output of the view if teaser is set as viewmode.');
    }
  }

}
