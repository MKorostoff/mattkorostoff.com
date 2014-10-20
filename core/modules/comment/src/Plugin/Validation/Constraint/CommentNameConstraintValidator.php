<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\Validation\Constraint\CommentNameConstraintValidator.
 */

namespace Drupal\comment\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the CommentName constraint.
 */
class CommentNameConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    $author_name = $items->first()->value;
    if (isset($author_name) && $author_name !== '') {
      // Do not allow unauthenticated comment authors to use a name that is
      // taken by a registered user.
      if ($items->getEntity()->getOwnerId() === 0) {
        // @todo Properly inject dependency https://drupal.org/node/2197029
        $users = \Drupal::entityManager()->getStorage('user')->loadByProperties(array('name' => $author_name));
        if (!empty($users)) {
          $this->context->addViolation($constraint->message, array('%name' => $author_name));
        }
      }
    }
  }

}
