<?php

/**
 * @file
 * Contains \Drupal\system\Entity\Menu.
 */

namespace Drupal\system\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\system\MenuInterface;

/**
 * Defines the Menu configuration entity class.
 *
 * @ConfigEntityType(
 *   id = "menu",
 *   label = @Translation("Menu"),
 *   handlers = {
 *     "access" = "Drupal\system\MenuAccessControlHandler"
 *   },
 *   admin_permission = "administer menu",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   }
 * )
 */
class Menu extends ConfigEntityBase implements MenuInterface {

  /**
   * The menu machine name.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the menu entity.
   *
   * @var string
   */
  public $label;

  /**
   * The menu description.
   *
   * @var string
   */
  public $description;

  /**
   * The locked status of this menu.
   *
   * @var bool
   */
  protected $locked = FALSE;

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    return (bool) $this->locked;
  }

}
