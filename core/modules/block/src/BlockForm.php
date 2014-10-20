<?php

/**
 * @file
 * Contains \Drupal\block\BlockForm.
 */

namespace Drupal\block;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides form for block instance forms.
 */
class BlockForm extends EntityForm {

  /**
   * The block entity.
   *
   * @var \Drupal\block\BlockInterface
   */
  protected $entity;

  /**
   * The block storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Constructs a BlockForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->storage = $entity_manager->getStorage('block');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    // Store theme settings in $form_state for use below.
    if (!$theme = $entity->get('theme')) {
      $theme = $this->config('system.theme')->get('default');
    }
    $form_state->set('block_theme', $theme);

    $form['#tree'] = TRUE;
    $form['settings'] = $entity->getPlugin()->buildConfigurationForm(array(), $form_state);

    // If creating a new block, calculate a safe default machine name.
    $form['id'] = array(
      '#type' => 'machine_name',
      '#maxlength' => 64,
      '#description' => $this->t('A unique name for this block instance. Must be alpha-numeric and underscore separated.'),
      '#default_value' => !$entity->isNew() ? $entity->id() : $this->getUniqueMachineName($entity),
      '#machine_name' => array(
        'exists' => '\Drupal\block\Entity\Block::load',
        'replace_pattern' => '[^a-z0-9_.]+',
        'source' => array('settings', 'label'),
      ),
      '#required' => TRUE,
      '#disabled' => !$entity->isNew(),
    );

    // Theme settings.
    if ($entity->get('theme')) {
      $form['theme'] = array(
        '#type' => 'value',
        '#value' => $theme,
      );
    }
    else {
      $theme_options = array();
      foreach (list_themes() as $theme_name => $theme_info) {
        if (!empty($theme_info->status)) {
          $theme_options[$theme_name] = $theme_info->info['name'];
        }
      }
      $form['theme'] = array(
        '#type' => 'select',
        '#options' => $theme_options,
        '#title' => t('Theme'),
        '#default_value' => $theme,
        '#ajax' => array(
          'callback' => '::themeSwitch',
          'wrapper' => 'edit-block-region-wrapper',
        ),
      );
    }

    // Region settings.
    $form['region'] = array(
      '#type' => 'select',
      '#title' => $this->t('Region'),
      '#description' => $this->t('Select the region where this block should be displayed.'),
      '#default_value' => $entity->get('region'),
      '#empty_value' => BlockInterface::BLOCK_REGION_NONE,
      '#options' => system_region_list($theme, REGIONS_VISIBLE),
      '#prefix' => '<div id="edit-block-region-wrapper">',
      '#suffix' => '</div>',
    );
    $form['#attached']['css'] = array(
      drupal_get_path('module', 'block') . '/css/block.admin.css',
    );
    return $form;
  }

  /**
   * Handles switching the available regions based on the selected theme.
   */
  public function themeSwitch($form, FormStateInterface $form_state) {
    $form['region']['#options'] = system_region_list($form_state->getValue('theme'), REGIONS_VISIBLE);
    return $form['region'];
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save block');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state) {
    parent::validate($form, $form_state);

    // The Block Entity form puts all block plugin form elements in the
    // settings form element, so just pass that to the block for validation.
    $settings = (new FormState())->setValues($form_state->getValue('settings'));
    // Call the plugin validate handler.
    $this->entity->getPlugin()->validateConfigurationForm($form, $settings);
    // Update the original form values.
    $form_state->setValue('settings', $settings->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $entity = $this->entity;
    // The Block Entity form puts all block plugin form elements in the
    // settings form element, so just pass that to the block for submission.
    // @todo Find a way to avoid this manipulation.
    $settings = (new FormState())->setValues($form_state->getValue('settings'));

    // Call the plugin submit handler.
    $entity->getPlugin()->submitConfigurationForm($form, $settings);
    // Update the original form values.
    $form_state->setValue('settings', $settings->getValues());

    // Save the settings of the plugin.
    $entity->save();

    drupal_set_message($this->t('The block configuration has been saved.'));
    $form_state->setRedirect(
      'block.admin_display_theme',
      array(
        'theme' => $form_state->getValue('theme'),
      ),
      array('query' => array('block-placement' => drupal_html_class($this->entity->id())))
    );
  }

  /**
   * Generates a unique machine name for a block.
   *
   * @param \Drupal\block\BlockInterface $block
   *   The block entity.
   *
   * @return string
   *   Returns the unique name.
   */
  public function getUniqueMachineName(BlockInterface $block) {
    $suggestion = $block->getPlugin()->getMachineNameSuggestion();

    // Get all the blocks which starts with the suggested machine name.
    $query = $this->storage->getQuery();
    $query->condition('id', $suggestion, 'CONTAINS');
    $block_ids = $query->execute();

    $block_ids = array_map(function ($block_id) {
      $parts = explode('.', $block_id);
      return end($parts);
    }, $block_ids);

    // Iterate through potential IDs until we get a new one. E.g.
    // 'plugin', 'plugin_2', 'plugin_3'...
    $count = 1;
    $machine_default = $suggestion;
    while (in_array($machine_default, $block_ids)) {
      $machine_default = $suggestion . '_' . ++$count;
    }
    return $machine_default;
  }

}
