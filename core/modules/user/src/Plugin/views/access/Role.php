<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\views\access\Role.
 */

namespace Drupal\user\Plugin\views\access;

use Drupal\Component\Utility\String;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\Routing\Route;
use Drupal\Core\Session\AccountInterface;

/**
 * Access plugin that provides role-based access control.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "role",
 *   title = @Translation("Role"),
 *   help = @Translation("Access will be granted to users with any of the specified roles.")
 * )
 */
class Role extends AccessPluginBase {

  /**
   * Overrides Drupal\views\Plugin\Plugin::$usesOptions.
   */
  protected $usesOptions = TRUE;

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return $account->hasPermission('access all views') || array_intersect(array_filter($this->options['role']), $account->getRoles());
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    if ($this->options['role']) {
      $route->setRequirement('_role', (string) implode(',', $this->options['role']));
    }
  }

  public function summaryTitle() {
    $count = count($this->options['role']);
    if ($count < 1) {
      return $this->t('No role(s) selected');
    }
    elseif ($count > 1) {
      return $this->t('Multiple roles');
    }
    else {
      $rids = user_role_names();
      $rid = reset($this->options['role']);
      return String::checkPlain($rids[$rid]);
    }
  }


  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['role'] = array('default' => array());

    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['role'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Role'),
      '#default_value' => $this->options['role'],
      '#options' => array_map('\Drupal\Component\Utility\String::checkPlain', user_role_names()),
      '#description' => $this->t('Only the checked roles will be able to access this display. Note that users with "access all views" can see any view, regardless of role.'),
    );
  }

  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    $role = $form_state->getValue(array('access_options', 'role'));
    $role = array_filter($role);

    if (!$role) {
      $form_state->setError($form['role'], $this->t('You must select at least one role if type is "by role"'));
    }

    $form_state->setValue(array('access_options', 'role'), $role);
  }

}
