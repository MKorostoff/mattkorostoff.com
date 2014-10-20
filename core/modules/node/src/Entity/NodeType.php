<?php

/**
 * @file
 * Contains \Drupal\node\Entity\NodeType.
 */

namespace Drupal\node\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Config\Entity\ThirdPartySettingsTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\node\NodeTypeInterface;

/**
 * Defines the Node type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "node_type",
 *   label = @Translation("Content type"),
 *   handlers = {
 *     "access" = "Drupal\node\NodeTypeAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\node\NodeTypeForm",
 *       "edit" = "Drupal\node\NodeTypeForm",
 *       "delete" = "Drupal\node\Form\NodeTypeDeleteConfirm"
 *     },
 *     "list_builder" = "Drupal\node\NodeTypeListBuilder",
 *   },
 *   admin_permission = "administer content types",
 *   config_prefix = "type",
 *   bundle_of = "node",
 *   entity_keys = {
 *     "id" = "type",
 *     "label" = "name"
 *   },
 *   links = {
 *     "edit-form" = "entity.node_type.edit_form",
 *     "delete-form" = "entity.node_type.delete_form"
 *   }
 * )
 */
class NodeType extends ConfigEntityBundleBase implements NodeTypeInterface {
  use ThirdPartySettingsTrait;

  /**
   * The machine name of this node type.
   *
   * @var string
   *
   * @todo Rename to $id.
   */
  public $type;

  /**
   * The human-readable name of the node type.
   *
   * @var string
   *
   * @todo Rename to $label.
   */
  public $name;

  /**
   * A brief description of this node type.
   *
   * @var string
   */
  public $description;

  /**
   * Help information shown to the user when creating a Node of this type.
   *
   * @var string
   */
  public $help;

  /**
   * Default value of the 'Create new revision' checkbox of this node type.
   *
   * @var bool
   */
  protected $new_revision = FALSE;

  /**
   * The preview mode.
   *
   * @var int
   */
  protected $preview_mode = DRUPAL_OPTIONAL;

  /**
   * Display setting for author and date Submitted by post information.
   *
   * @var bool
   */
  protected $display_submitted = TRUE;

  /**
   * Indicates whether a Body field should be created for this node type.
   *
   * This property affects entity creation only. It allows default configuration
   * of modules and installation profiles to specify whether a Body field should
   * be created for this bundle.
   *
   * @var bool
   *
   * @see \Drupal\node\Entity\NodeType::$create_body_label
   */
  protected $create_body = TRUE;

  /**
   * The label to use for the Body field upon entity creation.
   *
   * @see \Drupal\node\Entity\NodeType::$create_body
   *
   * @var string
   */
  protected $create_body_label = 'Body';

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    $locked = \Drupal::state()->get('node.type.locked');
    return isset($locked[$this->id()]) ? $locked[$this->id()] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isNewRevision() {
    return $this->new_revision;
  }

  /**
   * {@inheritdoc}
   */
  public function setNewRevision($new_revision) {
    $this->new_revision = $new_revision;
  }

  /**
   * {@inheritdoc}
   */
  public function displaySubmitted() {
    return $this->display_submitted;
  }

  /**
   * {@inheritdoc}
   */
  public function setDisplaySubmitted($display_submtited) {
    $this->display_submitted = $display_submtited;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviewMode() {
    return $this->preview_mode;
  }

  /**
   * {@inheritdoc}
   */
  public function setPreviewMode($preview_mode) {
    $this->preview_mode = $preview_mode;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if (!$update) {
      // Create a body if the create_body property is true and we're not in
      // the syncing process.
      if ($this->get('create_body') && !$this->isSyncing()) {
        $label = $this->get('create_body_label');
        node_add_body_field($this, $label);
      }
    }
    elseif ($this->getOriginalId() != $this->id()) {
      $update_count = node_type_update_nodes($this->getOriginalId(), $this->id());
      if ($update_count) {
        drupal_set_message(format_plural($update_count,
          'Changed the content type of 1 post from %old-type to %type.',
          'Changed the content type of @count posts from %old-type to %type.',
          array(
            '%old-type' => $this->getOriginalId(),
            '%type' => $this->id(),
          )));
      }
    }
    if ($update) {
      // Clear the cached field definitions as some settings affect the field
      // definitions.
      $this->entityManager()->clearCachedFieldDefinitions();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    // Clear the node type cache to reflect the removal.
    $storage->resetCache(array_keys($entities));
  }

}
