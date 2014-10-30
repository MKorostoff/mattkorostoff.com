<?php

/**
 * @file
 * Contains \Drupal\matthew\Plugin\Block\NextPreviousBlock.
 */

namespace Drupal\matthew\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;


/**
 * Provides a 'Next Previous' block.
 *
 * @Block(
 *   id = "next_previous_block",
 *   admin_label = @Translation("Next Previous Block"),
 *   category = @Translation("Blocks")
 * )
 */
class NextPreviousBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    //Get the created time of the current node
    $node = \Drupal::request()->attributes->get('node');
    $created_time = $node->getCreatedTime();
    $link = "";

    $link .= $this->_generate_previous($created_time);
    $link .= $this->_generate_next($created_time);

    return array('#markup' => $link);
  }

  private function _generate_previous($created_time) {
    //Lookup 1 node older than the current node
    $query = \Drupal::entityQuery('node');
    $previous = $query->condition('created', $created_time, '<')
      ->condition('type', 'article')
      ->sort('created', 'DESC')
      ->range(0, 1)
      ->execute();

    // If the currently viewed node has at least 1 node older than itself, build
    // a "Previous" link
    if (!empty($previous) && is_array($previous)) {
      $previous = array_values($previous);
      $previous = $previous[0];

      //Find the alias of the previous node
      $previous_url = \Drupal::service('path.alias_manager')->getAliasByPath('node/' . $previous);

      //Build the URL of the previous node
      $previous_url = Url::fromUri('base://' . $previous_url);

      //Build the HTML for the previous node
      return \Drupal::l(t('Previous Article'), $previous_url);
    }
  }

  private function _generate_next($created_time) {
    //Lookup 1 node younger than the current node
    $query = \Drupal::entityQuery('node');
    $next = $query->condition('created', $created_time, '>')
      ->condition('type', 'article')
      ->sort('created', 'ASC')
      ->range(0, 1)
      ->execute();

    //If this is not the youngest node
    if (!empty($next) && is_array($next)) {
      $next = array_values($next);
      $next = $next[0];

      //Find the alias of the previous node
      $next_url = \Drupal::service('path.alias_manager')->getAliasByPath('node/' . $next);

      //Build the URL of the previous node
      $next_url = Url::fromUri('base://' . $next_url);

      //Build the HTML for the previous node
      return \Drupal::l(t('Next Article'), $next_url);
    }
  }

}
