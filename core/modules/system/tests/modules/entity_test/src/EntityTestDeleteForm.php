<?php

/**
 * @file
 * Contains \Drupal\entity_test\EntityTestDeleteForm.
 */

namespace Drupal\entity_test;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides the entity_test delete form.
 */
class EntityTestDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('<front>');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $entity_type = $this->entity->getEntityType();
    return t('Are you sure you want to delete the %label @entity-type?', array('%label' => $this->entity->label(), '@entity-type' => $entity_type->getLowercaseLabel()));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $entity = $this->entity;
    $entity->delete();
    drupal_set_message(t('%entity_type @id has been deleted.', array('@id' => $entity->id(), '%entity_type' => $entity->getEntityTypeId())));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
