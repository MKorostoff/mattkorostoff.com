<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\area\Entity.
 */

namespace Drupal\views\Plugin\views\area;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Provides an area handler which renders an entity in a certain view mode.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("entity")
 */
class Entity extends TokenizeAreaPluginBase {

  /**
   * Stores the entity type of the result entities.
   *
   * @var string
   */
  protected $entityType;

  /**
   * Overrides \Drupal\views\Plugin\views\area\AreaPluginBase::init().
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->entityType = $this->definition['entity_type'];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    // Per default we enable tokenize, as this is the most common use case for
    // this handler.
    $options['tokenize']['default'] = TRUE;

    $options['entity_id'] = array('default' => '');
    $options['view_mode'] = array('default' => 'default');
    $options['bypass_access'] = array('default' => FALSE);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['view_mode'] = array(
      '#type' => 'select',
      '#options' => \Drupal::entityManager()->getViewModeOptions($this->entityType),
      '#title' => $this->t('View mode'),
      '#default_value' => $this->options['view_mode'],
    );

    $form['entity_id'] = array(
      '#title' => $this->t('ID'),
      '#type' => 'textfield',
      '#default_value' => $this->options['entity_id'],
    );

    $form['bypass_access'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Bypass access checks'),
      '#description' => $this->t('If enabled, access permissions for rendering the entity are not checked.'),
      '#default_value' => !empty($this->options['bypass_access']),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    if (!$empty || !empty($this->options['empty'])) {
      $entity_id = $this->tokenizeValue($this->options['entity_id']);
      $entity = entity_load($this->entityType, $entity_id);
      if ($entity && (!empty($this->options['bypass_access']) || $entity->access('view'))) {
        return entity_view($entity, $this->options['view_mode']);
      }
    }

    return array();
  }

}
