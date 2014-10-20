<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Plugin\CacheTest.
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\views\Views;
use Drupal\views\ViewExecutable;

/**
 * Tests pluggable caching for views.
 *
 * @group views
 * @see views_plugin_cache
 */
class CacheTest extends PluginTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view', 'test_cache');

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();
  }

  /**
   * Tests time based caching.
   *
   * @see views_plugin_cache_time
   */
  public function testTimeCaching() {
    // Create a basic result which just 2 results.
    $view = Views::getView('test_cache');
    $view->setDisplay();
    $view->display_handler->overrideOption('cache', array(
      'type' => 'time',
      'options' => array(
        'results_lifespan' => '3600',
        'output_lifespan' => '3600'
      )
    ));

    $this->executeView($view);
    // Verify the result.
    $this->assertEqual(5, count($view->result), 'The number of returned rows match.');

    // Add another man to the beatles.
    $record = array(
      'name' => 'Rod Davis',
      'age' => 29,
      'job' => 'Banjo',
    );
    db_insert('views_test_data')->fields($record)->execute();

    // The Result should be the same as before, because of the caching.
    $view = Views::getView('test_cache');
    $view->setDisplay();
    $view->display_handler->overrideOption('cache', array(
      'type' => 'time',
      'options' => array(
        'results_lifespan' => '3600',
        'output_lifespan' => '3600'
      )
    ));

    $this->executeView($view);
    // Verify the result.
    $this->assertEqual(5, count($view->result), 'The number of returned rows match.');
  }

  /**
   * Tests no caching.
   *
   * @see views_plugin_cache_time
   */
  function testNoneCaching() {
    // Create a basic result which just 2 results.
    $view = Views::getView('test_cache');
    $view->setDisplay();
    $view->display_handler->overrideOption('cache', array(
      'type' => 'none',
      'options' => array()
    ));

    $this->executeView($view);
    // Verify the result.
    $this->assertEqual(5, count($view->result), 'The number of returned rows match.');

    // Add another man to the beatles.
    $record = array(
      'name' => 'Rod Davis',
      'age' => 29,
      'job' => 'Banjo',
    );
    db_insert('views_test_data')->fields($record)->execute();

    // The Result changes, because the view is not cached.
    $view = Views::getView('test_cache');
    $view->setDisplay();
    $view->display_handler->overrideOption('cache', array(
      'type' => 'none',
      'options' => array()
    ));

    $this->executeView($view);
    // Verify the result.
    $this->assertEqual(6, count($view->result), 'The number of returned rows match.');
  }

  /**
   * Tests css/js storage and restoring mechanism.
   */
  function testHeaderStorage() {
    // Create a view with output caching enabled.
    // Some hook_views_pre_render in views_test_data.module adds the test css/js file.
    // so they should be added to the css/js storage.
    $view = Views::getView('test_view');
    $view->setDisplay();
    $view->storage->set('id', 'test_cache_header_storage');
    $view->display_handler->overrideOption('cache', array(
      'type' => 'time',
      'options' => array(
        'output_lifespan' => '3600'
      )
    ));

    $output = $view->preview();
    drupal_render($output);
    unset($view->pre_render_called);
    $view->destroy();

    $view->setDisplay();
    $output = $view->preview();
    drupal_render($output);
    $css_path = drupal_get_path('module', 'views_test_data') . '/views_cache.test.css';
    $js_path = drupal_get_path('module', 'views_test_data') . '/views_cache.test.js';
    $this->assertTrue(in_array($css_path, $output['#attached']['css']), 'Make sure the css is added for cached views.');
    $this->assertTrue(in_array($js_path, $output['#attached']['js']), 'Make sure the js is added for cached views.');
    $this->assertFalse(!empty($view->build_info['pre_render_called']), 'Make sure hook_views_pre_render is not called for the cached view.');

    // Now add some css/jss before running the view.
    // Make sure that this css is not added when running the cached view.
    $view->storage->set('id', 'test_cache_header_storage_2');
    $attached = array(
      '#attached' => array(
        'css' => array(
          drupal_get_path('module', 'system') . '/css/system.maintenance.css' => array(),
        ),
        'js' => array(
          drupal_get_path('module', 'user') . '/user.permissions.js' => array(),
        ),
      ),
    );
    drupal_render($attached);
    drupal_process_attached($attached);
    $view->destroy();

    $output = $view->preview();
    drupal_render($output);
    $this->assertTrue(empty($output['#attached']['css']), 'The view does not have attached CSS.');
    $this->assertTrue(empty($output['#attached']['js']), 'The view does not have attached JS.');
    $view->destroy();

    $output = $view->preview();
    drupal_render($output);
    $this->assertTrue(empty($output['#attached']['css']), 'The cached view does not have attached CSS.');
    $this->assertTrue(empty($output['#attached']['js']), 'The cached view does not have attached JS.');
  }

}
