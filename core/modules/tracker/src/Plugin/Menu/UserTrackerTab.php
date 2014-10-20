<?php

/**
 * @file
 * Contains \Drupal\tracker\Plugin\Menu\UserTrackerTab.
 */

namespace Drupal\tracker\Plugin\Menu;

use Drupal\Core\Menu\LocalTaskDefault;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route parameters needed to link to the current user tracker tab.
 */
class UserTrackerTab extends LocalTaskDefault {

  /**
   * Current user object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Gets the current active user.
   *
   * @todo: https://drupal.org/node/2105123 put this method in
   *   \Drupal\Core\Plugin\PluginBase instead.
   *
   * @return \Drupal\Core\Session\AccountInterface
   */
  protected function currentUser() {
    if (!$this->currentUser) {
      $this->currentUser = \Drupal::currentUser();
    }
    return $this->currentUser;
  }


  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(Request $request) {
    return array('user' => $this->currentUser()->Id());
  }

}
