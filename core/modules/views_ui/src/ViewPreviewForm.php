<?php

/**
 * @file
 * Contains Drupal\views_ui\ViewPreviewForm.
 */

namespace Drupal\views_ui;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\TempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the Views preview form.
 */
class ViewPreviewForm extends ViewFormBase {

  /**
   * The views temp store.
   *
   * @var \Drupal\user\TempStore
   */
  protected $tempStore;

  /**
   * Constructs a new ViewPreviewForm object.
   *
   * @param \Drupal\user\TempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   */
  public function __construct(TempStoreFactory $temp_store_factory) {
    $this->tempStore = $temp_store_factory->get('views');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.tempstore')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $view = $this->entity;

    $form['#prefix'] = '<div id="views-preview-wrapper" class="views-admin clearfix">';
    $form['#suffix'] = '</div>';
    $form['#id'] = 'views-ui-preview-form';

    // Reset the cache of IDs. Drupal rather aggressively prevents ID
    // duplication but this causes it to remember IDs that are no longer even
    // being used.
    $seen_ids_init = &drupal_static('drupal_html_id:init');
    $seen_ids_init = array();

    $form_state->disableCache();

    $form['controls']['#attributes'] = array('class' => array('clearfix'));

    // Add a checkbox controlling whether or not this display auto-previews.
    $form['controls']['live_preview'] = array(
      '#type' => 'checkbox',
      '#id' => 'edit-displays-live-preview',
      '#title' => $this->t('Auto preview'),
      '#default_value' => \Drupal::config('views.settings')->get('ui.always_live_preview'),
    );

    // Add the arguments textfield
    $form['controls']['view_args'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Preview with contextual filters:'),
      '#description' => $this->t('Separate contextual filter values with a "/". For example, %example.', array('%example' => '40/12/10')),
      '#id' => 'preview-args',
    );

    $args = array();
    if (!$form_state->isValueEmpty('view_args')) {
      $args = explode('/', $form_state->getValue('view_args'));
    }

    $user_input = $form_state->getUserInput();
    if ($form_state->get('show_preview') || !empty($user_input['js'])) {
      $form['preview'] = array(
        '#weight' => 110,
        '#theme_wrappers' => array('container'),
        '#attributes' => array('id' => 'views-live-preview'),
        '#markup' => $view->renderPreview($this->displayID, $args),
      );
    }
    $uri = $view->urlInfo('preview-form');
    $uri->setRouteParameter('display_id', $this->displayID);
    $form['#action'] = $uri->toString();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $view = $this->entity;
    return array(
      '#attributes' => array(
        'id' => 'preview-submit-wrapper',
      ),
      'button' => array(
        '#type' => 'submit',
        '#value' => $this->t('Update preview'),
        '#attributes' => array('class' => array('arguments-preview')),
        '#submit' => array('::submitPreview'),
        '#id' => 'preview-submit',
        '#ajax' => array(
          'path' => 'admin/structure/views/view/' . $view->id() . '/preview/' . $this->displayID,
          'wrapper' => 'views-preview-wrapper',
          'event' => 'click',
          'progress' => array('type' => 'fullscreen'),
          'method' => 'replaceWith',
        ),
      ),
    );
  }

  /**
   * Form submission handler for the Preview button.
   */
  public function submitPreview($form, FormStateInterface $form_state) {
    // Rebuild the form with a pristine $view object.
    $view = $this->entity;
    // Attempt to load the view from temp store, otherwise create a new one.
    if (!$new_view = $this->tempStore->get($view->id())) {
      $new_view = new ViewUI($view);
    }
    $build_info = $form_state->getBuildInfo();
    $build_info['args'][0] = $new_view;
    $form_state->setBuildInfo($build_info);
    $form_state->set('show_preview', TRUE);
    $form_state->setRebuild();
  }

}
