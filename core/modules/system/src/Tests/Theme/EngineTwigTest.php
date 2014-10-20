<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Theme\EngineTwigTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Tests Twig-specific theme functionality.
 *
 * @group Theme
 */
class EngineTwigTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('theme_test', 'twig_theme_test');

  protected function setUp() {
    parent::setUp();
    \Drupal::service('theme_handler')->install(array('test_theme'));
  }

  /**
   * Tests that the Twig engine handles PHP data correctly.
   */
  function testTwigVariableDataTypes() {
    \Drupal::config('system.theme')
      ->set('default', 'test_theme')
      ->save();
    $this->drupalGet('twig-theme-test/php-variables');
    foreach (_test_theme_twig_php_values() as $type => $value) {
      $this->assertRaw('<li>' . $type . ': ' . $value['expected'] . '</li>');
    }
  }

  /**
   * Tests the url and url_generate Twig functions.
   */
  public function testTwigUrlGenerator() {
    $this->drupalGet('twig-theme-test/url-generator');
    // Find the absolute URL of the current site.
    $url_generator = $this->container->get('url_generator');
    $expected = array(
      'path (as route) not absolute: ' . $url_generator->generateFromRoute('user.register'),
      'url (as route) absolute: ' . $url_generator->generateFromRoute('user.register', array(), array('absolute' => TRUE)),
      'path (as route) not absolute with fragment: ' . $url_generator->generateFromRoute('user.register', array(), array('fragment' => 'bottom')),
      'url (as route) absolute despite option: ' . $url_generator->generateFromRoute('user.register', array(), array('absolute' => TRUE)),
      'url (as route) absolute with fragment: ' . $url_generator->generateFromRoute('user.register', array(), array('absolute' => TRUE, 'fragment' => 'bottom')),
    );
    // Make sure we got something.
    $content = $this->drupalGetContent();
    $this->assertFalse(empty($content), 'Page content is not empty');
    foreach ($expected as $string) {
      $this->assertRaw('<div>' . $string . '</div>');
    }
  }

  /**
   * Tests the link_generator twig functions.
   */
  public function testTwigLinkGenerator() {
    $this->drupalGet('twig-theme-test/link-generator');

    $link_generator = $this->container->get('link_generator');

    $expected = [
      'link via the linkgenerator: ' . $link_generator->generate('register', new Url('user.register')),
    ];

    $content = $this->getRawContent();
    $this->assertFalse(empty($content), 'Page content is not empty');
    foreach ($expected as $string) {
      $this->assertRaw('<div>' . $string . '</div>');
    }
  }

}
