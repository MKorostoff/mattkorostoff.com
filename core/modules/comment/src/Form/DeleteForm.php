<?php

/**
 * @file
 * Contains \Drupal\comment\Form\DeleteForm.
 */

namespace Drupal\comment\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the comment delete confirmation form.
 */
class DeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the comment %title?', array('%title' => $this->entity->subject->value));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    // Point to the entity of which this comment is a reply.
    return $this->entity->get('entity_id')->entity->urlInfo();
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Any replies to this comment will be lost. This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Delete the comment and its replies.
    $this->entity->delete();
    drupal_set_message($this->t('The comment and all its replies have been deleted.'));
    $this->logger('content')->notice('Deleted comment @cid and its replies.', array('@cid' => $this->entity->id()));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
