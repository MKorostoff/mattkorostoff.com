<?php

/**
 * @file
 * Contains \Drupal\devel\Form\SettingsForm.
 */

namespace Drupal\devel\Form;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a form that configures devel settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'devel_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $current_path = $request->attributes->get('_system_path');
    $current_url = Url::createFromRequest($request);
    $devel_config = $this->config('devel.settings');

    $form['queries'] = array('#type' => 'fieldset', '#title' => t('Query log'));

    $description = t('Display a log of the database queries needed to generate the current page, and the execution time for each. Also, queries which are repeated during a single page view are summed in the # column, and printed in red since they are candidates for caching.');
    $form['queries']['query_display'] = array('#type' => 'checkbox',
      '#title' => t('Display query log'),
      '#default_value' => $devel_config->get('query_display'),
      '#description' => $description,
    );
    $form['queries']['settings'] = array(
      '#type' => 'container',
        '#states' => array(
          // Hide the query log settings when not displaying query log.
          'invisible' => array(
            'input[name="query_display"]' => array('checked' => FALSE),
          ),
        ),
    );
    $form['queries']['settings']['query_sort'] = array('#type' => 'radios',
      '#title' => t('Sort query log'),
      '#default_value' =>   $devel_config->get('query_sort'),
      '#options' => array(t('by source'), t('by duration')),
      '#description' => t('The query table can be sorted in the order that the queries were executed or by descending duration.'),
    );
    $form['queries']['settings']['execution'] = array('#type' => 'textfield',
      '#title' => t('Slow query highlighting'),
      '#default_value' => $devel_config->get('execution'),
      '#size' => 4,
      '#maxlength' => 4,
      '#description' => t('Enter an integer in milliseconds. Any query which takes longer than this many milliseconds will be highlighted in the query log. This indicates a possibly inefficient query, or a candidate for caching.'),
    );

    $form['api_url'] = array('#type' => 'textfield',
      '#title' => t('API Site'),
      '#default_value' => $devel_config->get('api_url'),
      '#description' => t('The base URL for your developer documentation links. You might change this if you run <a href="!url">api.module</a> locally.', array('!url' => Url::fromUri('http://drupal.org/project/api')->toString())));


    $form['timer'] = array('#type' => 'checkbox',
      '#title' => t('Display page timer'),
      '#default_value' => $devel_config->get('timer'),
      '#description' => t('Display page execution time in the query log box.'),
    );

    $form['memory'] = array('#type' => 'checkbox',
      '#title' => t('Display memory usage'),
      '#default_value' => $devel_config->get('memory'),
      '#description' => t('Display how much memory is used to generate the current page. This will show memory usage when devel_init() is called and when devel_exit() is called.'),
    );
    $form['redirect_page'] = array('#type' => 'checkbox',
      '#title' => t('Display redirection page'),
      '#default_value' => $devel_config->get('redirect_page'),
      '#description' => t('When a module executes drupal_goto(), the query log and other developer information is lost. Enabling this setting presents an intermediate page to developers so that the log can be examined before continuing to the destination page.'),
    );
    $form['page_alter'] = array('#type' => 'checkbox',
      '#title' => t('Display $page array'),
      '#default_value' => $devel_config->get('page_alter'),
      '#description' => t('Display $page array from <a href="http://api.drupal.org/api/function/hook_page_alter/7">hook_page_alter()</a> in the messages area of each page.'),
    );
    $form['raw_names'] = array('#type' => 'checkbox',
      '#title' => t('Display machine names of permissions and modules'),
      '#default_value' => $devel_config->get('raw_names'),
      '#description' => t('Display the language-independent machine names of the permissions in mouse-over hints on the !Permissions page and the module base file names on the @Permissions and !Modules pages.', array('!Permissions' => $this->l(t('Permissions'), Url::fromRoute('user.admin_permissions')), '@Permissions' => t('Permissions'), '!Modules' => $this->l(t('Modules'), Url::fromRoute('system.modules_list')))),
    );

    $error_handlers = devel_get_handlers();
    $form['error_handlers'] = array(
      '#type' => 'select',
      '#title' => t('Error handlers'),
      '#options' => array(
        DEVEL_ERROR_HANDLER_NONE => t('None'),
        DEVEL_ERROR_HANDLER_STANDARD => t('Standard Drupal'),
        DEVEL_ERROR_HANDLER_BACKTRACE_DPM => t('Krumo backtrace in the message area'),
        DEVEL_ERROR_HANDLER_BACKTRACE_KRUMO => t('Krumo backtrace above the rendered page'),
      ),
      '#multiple' => TRUE,
      '#default_value' => empty($error_handlers) ? DEVEL_ERROR_HANDLER_NONE : $error_handlers,
      '#description' => SafeMarkup::set(t('Select the error handler(s) to use, in case you <a href="@choose">choose to show errors on screen</a>.', array('@choose' => $this->url('system.logging_settings'))) . '<ul>' .
          '<li>' . t('<em>None</em> is a good option when stepping through the site in your debugger.') . '</li>' .
          '<li>' . t('<em>Standard Drupal</em> does not display all the information that is often needed to resolve an issue.') . '</li>' .
          '<li>' . t('<em>Krumo backtrace</em> displays nice debug information when any type of error is noticed, but only to users with the %perm permission.', array('%perm' => t('Access developer information'))) . '</li></ul>' .
          t('Depending on the situation, the theme, the size of the call stack and the arguments, etc., some handlers may not display their messages, or display them on the subsequent page. Select <em>Standard Drupal</em> <strong>and</strong> <em>Krumo backtrace above the rendered page</em> to maximize your chances of not missing any messages.') . '<br />' .
          t('Demonstrate the current error handler(s):') . ' ' .
          $this->l('notice', $current_url, array('query' => array('demo' => 'notice'))) . ', ' .
          $this->l('notice+warning', $current_url, array('query' => array('demo' => 'warning'))) . ', ' .
          $this->l('notice+warning+error', $current_url, array('query' => array('demo' => 'error'))) . ' ' .
          t('(The presentation of the @error is determined by PHP.)', array('@error' => 'error'))),
    );
    $form['error_handlers']['#size'] = count($form['error_handlers']['#options']);
    if ($request->query->has('demo')) {
      if ($request->getMethod() == 'GET') {
        $this->demonstrateErrorHandlers($request->query->get('demo'));
      }
      $request->query->remove('demo');
    }

    $options = array('default', 'blue', 'green', 'orange', 'white', 'disabled');
    $form['krumo_skin'] = array(
      '#type' => 'radios',
      '#title' => t('Krumo display'),
      '#description' => t('Select a skin for your debug messages or select <em>disabled</em> to display object and array output in standard PHP format.'),
      '#options' => array_combine($options, $options),
      '#default_value' => $devel_config->get('krumo_skin'),
    );

    $form['rebuild_theme'] = array(
     '#type' => 'checkbox',
     '#title' => t('Rebuild the theme information like the registry'),
     '#description' => t('While creating new templates, change the $theme.info.yml and theme_ overrides the theme information needs to be rebuilt.'),
     '#default_value' => $devel_config->get('rebuild_theme'),
    );

    $form['use_uncompressed_jquery'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use uncompressed jQuery'),
      '#default_value' => $devel_config->get('use_uncompressed_jquery'),
      '#description' => t("Use a human-readable version of jQuery instead of the minified version that ships with Drupal, to make JavaScript debugging easier."),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('devel.settings')
      ->set('query_display', $values['query_display'])
      ->set('query_sort', $values['query_sort'])
      ->set('execution', $values['execution'])
      ->set('api_url', $values['api_url'])
      ->set('timer', $values['timer'])
      ->set('memory', $values['memory'])
      ->set('redirect_page', $values['redirect_page'])
      ->set('page_alter', $values['page_alter'])
      ->set('raw_names', $values['raw_names'])
      ->set('error_handlers', $values['error_handlers'])
      ->set('krumo_skin', $values['krumo_skin'])
      ->set('rebuild_theme', $values['rebuild_theme'])
      ->set('use_uncompressed_jquery', $values['use_uncompressed_jquery'])
      ->save();
  }


  /**
   * @param string $severity
   */
  protected function demonstrateErrorHandlers($severity) {
    switch ($severity) {
      case 'notice':
        $undefined = $undefined;
        break;
      case 'warning':
        $undefined = $undefined;
        1/0;
        break;
      case 'error':
        $undefined = $undefined;
        1/0;
        devel_undefined_function();
        break;
    }
  }

}
