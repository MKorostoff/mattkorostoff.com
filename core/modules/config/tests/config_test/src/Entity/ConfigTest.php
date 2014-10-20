<?php

/**
 * @file
 * Definition of Drupal\config_test\Entity\ConfigTest.
 */

namespace Drupal\config_test\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\config_test\ConfigTestInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the ConfigTest configuration entity.
 *
 * @ConfigEntityType(
 *   id = "config_test",
 *   label = @Translation("Test configuration"),
 *   handlers = {
 *     "storage" = "Drupal\config_test\ConfigTestStorage",
 *     "list_builder" = "Drupal\config_test\ConfigTestListBuilder",
 *     "form" = {
 *       "default" = "Drupal\config_test\ConfigTestForm",
 *       "delete" = "Drupal\config_test\Form\ConfigTestDeleteForm"
 *     },
 *     "access" = "Drupal\config_test\ConfigTestAccessControlHandler"
 *   },
 *   config_prefix = "dynamic",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status"
 *   },
 *   links = {
 *     "edit-form" = "entity.config_test.edit_form",
 *     "delete-form" = "entity.config_test.delete_form",
 *     "enable" = "entity.config_test.enable",
 *     "disable" = "entity.config_test.disable"
 *   }
 * )
 */
class ConfigTest extends ConfigEntityBase implements ConfigTestInterface {

  /**
   * The machine name for the configuration entity.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the configuration entity.
   *
   * @var string
   */
  public $label;

  /**
   * The weight of the configuration entity.
   *
   * @var int
   */
  public $weight = 0;

  /**
   * The image style to use.
   *
   * @var string
   */
  public $style;

  /**
   * Test dependencies.
   *
   * @var array;
   */
  public $test_dependencies = array();

  /**
   * A protected property of the configuration entity.
   *
   * @var string
   */
  protected $protected_property;

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigEntityBase::sort().
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {
    \Drupal::state()->set('config_entity_sort', TRUE);
    return parent::sort($a, $b);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    // Used to test secondary writes during config sync.
    if ($this->id() == 'primary') {
      $secondary = $storage->create(array(
        'id' => 'secondary',
        'label' => 'Secondary Default',
      ));
      $secondary->save();
    }
    if ($this->id() == 'deleter') {
      $deletee = $storage->load('deletee');
      $deletee->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    foreach ($entities as $entity) {
      if ($entity->id() == 'deleter') {
        $deletee = $storage->load('deletee');
        $deletee->delete();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    foreach ($this->test_dependencies as $type => $deps) {
      foreach ($deps as $dep) {
        $this->addDependency($type, $dep);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = FALSE;
    $fix_deps = \Drupal::state()->get('config_test.fix_dependencies', array());
    foreach ($dependencies['entity'] as $entity) {
      if (in_array($entity->getConfigDependencyName(), $fix_deps)) {
        $key = array_search($entity->getConfigDependencyName(), $this->test_dependencies['entity']);
        if ($key !== FALSE) {
          $changed = TRUE;
          unset($this->test_dependencies['entity'][$key]);
        }
      }
    }
    if ($changed) {
      $this->save();
    }
  }

}
