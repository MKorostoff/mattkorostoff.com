<?php

/**
 * @file
 * Contains \Drupal\twig_theme_test\TwigThemeTestController.
 */

namespace Drupal\twig_theme_test;

use Drupal\Core\Url;

/**
 * Controller routines for Twig theme test routes.
 */
class TwigThemeTestController {

  /**
   * Menu callback for testing PHP variables in a Twig template.
   */
  public function phpVariablesRender() {
    return \Drupal::theme()->render('twig_theme_test_php_variables', array());
  }

  /**
   * Menu callback for testing translation blocks in a Twig template.
   */
  public function transBlockRender() {
    return array(
      '#theme' => 'twig_theme_test_trans',
    );
  }

  /**
   * Renders for testing url_generator functions in a Twig template.
   */
  public function urlGeneratorRender() {
    return array(
      '#theme' => 'twig_theme_test_url_generator',
    );
  }

  /**
   * Renders for testing link_generator functions in a Twig template.
   */
  public function linkGeneratorRender() {
    return array(
      '#theme' => 'twig_theme_test_link_generator',
      '#test_url' => new Url('user.register'),
    );
  }

}
