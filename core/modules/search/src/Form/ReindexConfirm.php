<?php

/**
 * @file
 * Contains \Drupal\search\Form\ReindexConfirm.
 */

namespace Drupal\search\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides the search reindex confirmation form.
 */
class ReindexConfirm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_reindex_confirm';
  }

  /**
   * Implements \Drupal\Core\Form\ConfirmFormBase::getQuestion().
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to re-index the site?');
  }

  /**
   * Overrides \Drupal\Core\Form\ConfirmFormBase::getDescription().
   */
  public function getDescription() {
    return $this->t('The search index is not cleared but systematically updated to reflect the new settings. Searching will continue to work but new content won\'t be indexed until all existing content has been re-indexed. This action cannot be undone.');
  }

  /**
   * Overrides \Drupal\Core\Form\ConfirmFormBase::getConfirmText().
   */
  public function getConfirmText() {
    return $this->t('Re-index site');
  }

  /**
   * Overrides \Drupal\Core\Form\ConfirmFormBase::getCancelText().
   */
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('search.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form['confirm']) {
      search_reindex();
      drupal_set_message($this->t('The index will be rebuilt.'));
      $form_state->setRedirectUrl($this->getCancelUrl());
    }
  }
}
