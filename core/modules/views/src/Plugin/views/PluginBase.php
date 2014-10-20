<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\PluginBase.
 */

namespace Drupal\views\Plugin\views;

use Drupal\Component\Utility\String;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase as ComponentPluginBase;
use Drupal\Core\Render\Element;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for any views plugin types.
 *
 * Via the @Plugin definition the plugin may specify a theme function or
 * template to be used for the plugin. It also can auto-register the theme
 * implementation for that file or function.
 * - theme: the theme implementation to use in the plugin. This may be the name
 *   of the function (without theme_ prefix) or the template file (without
 *   template engine extension).
 *   If a template file should be used, the file has to be placed in the
 *   module's templates folder.
 *   Example: theme = "mymodule_row" of module "mymodule" will implement either
 *   theme_mymodule_row() or mymodule-row.html.twig in the
 *   [..]/modules/mymodule/templates folder.
 * - register_theme: (optional) When set to TRUE (default) the theme is
 *   registered automatically. When set to FALSE the plugin reuses an existing
 *   theme implementation, defined by another module or views plugin.
 * - theme_file: (optional) the location of an include file that may hold the
 *   theme or preprocess function. The location has to be relative to module's
 *   root directory.
 * - module: machine name of the module. It must be present for any plugin that
 *   wants to register a theme.
 *
 * @ingroup views_plugins
 */
abstract class PluginBase extends ComponentPluginBase implements ContainerFactoryPluginInterface, ViewsPluginInterface {

  /**
   * Include negotiated languages when listing languages.
   *
   * @see \Drupal\views\Plugin\views\PluginBase::listLanguages()
   */
  const INCLUDE_NEGOTIATED = 16;

  /**
   * Options for this plugin will be held here.
   *
   * @var array
   */
  public $options = array();

  /**
   * The top object of a view.
   *
   * @var \Drupal\views\ViewExecutable
   */
  public $view = NULL;

  /**
   * The display object this plugin is for.
   *
   * For display plugins this is empty.
   *
   * @todo find a better description
   *
   * @var \Drupal\views\Plugin\views\display\DisplayPluginBase
   */
  public $displayHandler;

  /**
   * Plugins's definition
   *
   * @var array
   */
  public $definition;

  /**
   * Denotes whether the plugin has an additional options form.
   *
   * @var bool
   */
  protected $usesOptions = FALSE;


  /**
   * Constructs a PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->definition = $plugin_definition + $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    $this->view = $view;
    $this->setOptionDefaults($this->options, $this->defineOptions());
    $this->displayHandler = $display;

    $this->unpackOptions($this->options, $options);
  }

  /**
   * Information about options for all kinds of purposes will be held here.
   * @code
   * 'option_name' => array(
   *  - 'default' => default value,
   *  - 'translatable' => (optional) TRUE/FALSE (wrap in $this->t() on export if true),
   *  - 'contains' => (optional) array of items this contains, with its own
   *      defaults, etc. If contains is set, the default will be ignored and
   *      assumed to be array().
   *  - 'bool' => (optional) TRUE/FALSE Is the value a boolean value. This will
   *      change the export format to TRUE/FALSE instead of 1/0.
   *  ),
   *
   * @return array
   *   Returns the options of this handler/plugin.
   */
  protected function defineOptions() { return array(); }

  /**
   * Fills up the options of the plugin with defaults.
   *
   * @param array $storage
   *   An array which stores the actual option values of the plugin.
   * @param array $options
   *   An array which describes the options of a plugin. Each element is an
   *   associative array containing:
   *   - default: The default value of one option
   *   - (optional) contains: An array which describes the available options
   *     under the key. If contains is set, the default will be ignored and
   *     assumed to be an empty array.
   *   - (optional) 'translatable': TRUE if it should be translated, else FALSE.
   *   - (optional) 'bool': TRUE if the value is boolean, else FALSE.
   */
  protected function setOptionDefaults(array &$storage, array $options) {
    foreach ($options as $option => $definition) {
      if (isset($definition['contains'])) {
        $storage[$option] = array();
        $this->setOptionDefaults($storage[$option], $definition['contains']);
      }
      else {
        $storage[$option] = $definition['default'];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function unpackOptions(&$storage, $options, $definition = NULL, $all = TRUE, $check = TRUE) {
    if ($check && !is_array($options)) {
      return;
    }

    if (!isset($definition)) {
      $definition = $this->defineOptions();
    }

    foreach ($options as $key => $value) {
      if (is_array($value)) {
        // Ignore arrays with no definition.
        if (!$all && empty($definition[$key])) {
          continue;
        }

        if (!isset($storage[$key]) || !is_array($storage[$key])) {
          $storage[$key] = array();
        }

        // If we're just unpacking our known options, and we're dropping an
        // unknown array (as might happen for a dependent plugin fields) go
        // ahead and drop that in.
        if (!$all && isset($definition[$key]) && !isset($definition[$key]['contains'])) {
          $storage[$key] = $value;
          continue;
        }

        $this->unpackOptions($storage[$key], $value, isset($definition[$key]['contains']) ? $definition[$key]['contains'] : array(), $all, FALSE);
      }
      else if ($all || !empty($definition[$key])) {
        $storage[$key] = $value;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function destroy() {
    unset($this->view, $this->display, $this->query);
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    // Some form elements belong in a fieldset for presentation, but can't
    // be moved into one because of the $form_state->getValues() hierarchy. Those
    // elements can add a #fieldset => 'fieldset_name' property, and they'll
    // be moved to their fieldset during pre_render.
    $form['#pre_render'][] = array(get_class($this), 'preRenderAddFieldsetMarkup');
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) { }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) { }

  /**
   * {@inheritdoc}
   */
  public function query() { }

  /**
   * {@inheritdoc}
   */
  public function themeFunctions() {
    return $this->view->buildThemeFunctions($this->definition['theme']);
  }

  /**
   * {@inheritdoc}
   */
  public function validate() { return array(); }

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    return $this->t('Settings');
  }

  /**
   * {@inheritdoc}
   */
  public function pluginTitle() {
    // Short_title is optional so its defaults to an empty string.
    if (!empty($this->definition['short_title'])) {
      return String::checkPlain($this->definition['short_title']);
    }
    return String::checkPlain($this->definition['title']);
  }

  /**
   * {@inheritdoc}
   */
  public function usesOptions() {
    return $this->usesOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function globalTokenReplace($string = '', array $options = array()) {
    return \Drupal::token()->replace($string, array('view' => $this->view), $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableGlobalTokens($prepared = FALSE, array $types = array()) {
    $info = \Drupal::token()->getInfo();
    // Site and view tokens should always be available.
    $types += array('site', 'view');
    $available = array_intersect_key($info['tokens'], array_flip($types));

    // Construct the token string for each token.
    if ($prepared) {
      $prepared = array();
      foreach ($available as $type => $tokens) {
        foreach (array_keys($tokens) as $token) {
          $prepared[$type][] = "[$type:$token]";
        }
      }

      return $prepared;
    }

    return $available;
  }

  /**
   * {@inheritdoc}
   */
  public function globalTokenForm(&$form, FormStateInterface $form_state) {
    $token_items = array();

    foreach ($this->getAvailableGlobalTokens() as $type => $tokens) {
      $item = array(
        '#markup' => $type,
        'children' => array(),
      );
      foreach ($tokens as $name => $info) {
        $item['children'][$name] = "[$type:$name]" . ' - ' . $info['name'] . ': ' . $info['description'];
      }

      $token_items[$type] = $item;
    }

    $form['global_tokens'] = array(
      '#type' => 'details',
      '#title' => $this->t('Available global token replacements'),
    );
    $form['global_tokens']['list'] = array(
      '#theme' => 'item_list',
      '#items' => $token_items,
      '#attributes' => array(
        'class' => array('global-tokens'),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderAddFieldsetMarkup(array $form) {
    foreach (Element::children($form) as $key) {
      $element = $form[$key];
      // In our form builder functions, we added an arbitrary #fieldset property
      // to any element that belongs in a fieldset. If this form element has
      // that property, move it into its fieldset.
      if (isset($element['#fieldset']) && isset($form[$element['#fieldset']])) {
        $form[$element['#fieldset']][$key] = $element;
        // Remove the original element this duplicates.
        unset($form[$key]);
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderFlattenData($form) {
    foreach (Element::children($form) as $key) {
      $element = $form[$key];
      if (!empty($element['#flatten'])) {
        foreach (Element::children($element) as $child_key) {
          $form[$child_key] = $form[$key][$child_key];
        }
        // All done, remove the now-empty parent.
        unset($form[$key]);
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array();
  }

  /**
   * Makes an array of languages, optionally including special languages.
   *
   * @param int $flags
   *   (optional) Flags for which languages to return (additive). Options:
   *   - \Drupal\Core\Language::STATE_ALL (default): All languages
   *     (configurable and default).
   *   - \Drupal\Core\Language::STATE_CONFIGURABLE: Configurable languages.
   *   - \Drupal\Core\Language::STATE_LOCKED: Locked languages.
   *   - \Drupal\Core\Language::STATE_SITE_DEFAULT: Add site default language;
   *     note that this is not included in STATE_ALL.
   *   - \Drupal\views\Plugin\views\PluginBase::INCLUDE_NEGOTIATED: Add
   *     negotiated language types.
   *
   * @return array
   *   An array of language names, keyed by the language code. Negotiated and
   *   special languages have special codes that are substituted in queries by
   *   static::queryLanguageSubstitutions().
   */
  protected function listLanguages($flags = LanguageInterface::STATE_ALL) {
    $manager = \Drupal::languageManager();
    $list = array();

    // The Language Manager class takes care of the STATE_SITE_DEFAULT case.
    // It comes in with ID set to 'site_default'. Since this is not a real
    // language, surround it by '***LANGUAGE_...***', like the negotiated
    // languages below.
    $languages = $manager->getLanguages($flags);
    foreach ($languages as $id => $language) {
      if ($id == 'site_default') {
        $id = '***LANGUAGE_' . $id . '***';
      }
      $list[$id] = $this->t($language->name);
    }

    // Add in negotiated languages, if requested.
    if ($flags & PluginBase::INCLUDE_NEGOTIATED) {
      $types = $manager->getDefinedLanguageTypesInfo();
      foreach ($types as $id => $type) {
        // Omit unnamed types. These are things like language_url, which are
        // not configurable and do not need to be in this list. And surround
        // IDs by '***LANGUAGE_...***', to avoid query collisions.
        if (isset($type['name'])) {
          $id = '***LANGUAGE_' . $id . '***';
          $list[$id] = $this->t('Language selected for !type', array('!type' => $type['name']));
        }
      }
    }

    return $list;
  }

  /**
   * Returns substitutions for Views queries for languages.
   *
   * This is needed so that the language options returned by
   * $this->listLanguages() are able to be used in queries. It is called
   * by the Views module implementation of hook_views_query_substitutions()
   * to get the language-related substitutions.
   *
   * @return array
   *   An array in the format of hook_views_query_substitutions() that gives
   *   the query substitutions needed for the special language types.
   */
  public static function queryLanguageSubstitutions() {
    $changes = array();
    $manager = \Drupal::languageManager();

    // Handle default language.
    $default = $manager->getDefaultLanguage()->id;
    $changes['***LANGUAGE_site_default***'] = $default;

    // Handle negotiated languages.
    $types = $manager->getDefinedLanguageTypesInfo();
    foreach ($types as $id => $type) {
      if (isset($type['name'])) {
        $changes['***LANGUAGE_' . $id . '***'] = $manager->getCurrentLanguage($id)->id;
      }
    }

    return $changes;
  }
}
