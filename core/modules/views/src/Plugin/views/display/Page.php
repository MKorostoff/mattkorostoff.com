<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\display\Page.
 */

namespace Drupal\views\Plugin\views\display;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The plugin that handles a full page.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "page",
 *   title = @Translation("Page"),
 *   help = @Translation("Display the view as a page, with a URL and menu links."),
 *   uses_menu_links = TRUE,
 *   uses_route = TRUE,
 *   contextual_links_locations = {"page"},
 *   theme = "views_view",
 *   admin = @Translation("Page")
 * )
 */
class Page extends PathPluginBase {

  /**
   * Whether the display allows attachments.
   *
   * @var bool
   */
  protected $usesAttachments = TRUE;

  /**
   * Overrides \Drupal\views\Plugin\views\display\PathPluginBase::defineOptions().
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['menu'] = array(
      'contains' => array(
        'type' => array('default' => 'none'),
        // Do not translate menu and title as menu system will.
        'title' => array('default' => '', 'translatable' => FALSE),
        'description' => array('default' => '', 'translatable' => FALSE),
        'weight' => array('default' => 0),
        'menu_name' => array('default' => 'main'),
        'parent' => array('default' => ''),
        'context' => array('default' => ''),
      ),
    );
    $options['tab_options'] = array(
      'contains' => array(
        'type' => array('default' => 'none'),
        // Do not translate menu and title as menu system will.
        'title' => array('default' => '', 'translatable' => FALSE),
        'description' => array('default' => '', 'translatable' => FALSE),
        'weight' => array('default' => 0),
        'menu_name' => array('default' => 'main'),
      ),
    );

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getRoute($view_id, $display_id) {
    $route = parent::getRoute($view_id, $display_id);

    // Move _controller to _content for page displays, which will return a
    // normal Drupal HTML page.
    $defaults = $route->getDefaults();
    $defaults['_content'] = $defaults['_controller'];
    unset($defaults['_controller']);
    $route->setDefaults($defaults);

    return $route;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\PathPluginBase::execute().
   */
  public function execute() {
    parent::execute();

    // Let the world know that this is the page view we're using.
    views_set_page_view($this->view);

    // And now render the view.
    $render = $this->view->render();

    // First execute the view so it's possible to get tokens for the title.
    // And the title, which is much easier.
    // @todo Figure out how to support custom response objects. Maybe for pages
    //   it should be dropped.
    if (is_array($render)) {
      $render += array(
        '#title' => Xss::filterAdmin($this->view->getTitle()),
      );
    }
    return $render;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::optionsSummary().
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);

    $menu = $this->getOption('menu');
    if (!is_array($menu)) {
      $menu = array('type' => 'none');
    }
    switch ($menu['type']) {
      case 'none':
      default:
        $menu_str = $this->t('No menu');
        break;
      case 'normal':
        $menu_str = $this->t('Normal: @title', array('@title' => $menu['title']));
        break;
      case 'tab':
      case 'default tab':
        $menu_str = $this->t('Tab: @title', array('@title' => $menu['title']));
        break;
    }

    $options['menu'] = array(
      'category' => 'page',
      'title' => $this->t('Menu'),
      'value' => views_ui_truncate($menu_str, 24),
    );

    // This adds a 'Settings' link to the style_options setting if the style
    // has options.
    if ($menu['type'] == 'default tab') {
      $options['menu']['setting'] = $this->t('Parent menu item');
      $options['menu']['links']['tab_options'] = $this->t('Change settings for the parent menu');
    }
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\callbackPluginBase::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    switch ($form_state->get('section')) {
      case 'menu':
        $form['#title'] .= $this->t('Menu item entry');
        $form['menu'] = array(
          '#prefix' => '<div class="clearfix">',
          '#suffix' => '</div>',
          '#tree' => TRUE,
        );
        $menu = $this->getOption('menu');
        if (empty($menu)) {
          $menu = array('type' => 'none', 'title' => '', 'weight' => 0);
        }
        $form['menu']['type'] = array(
          '#prefix' => '<div class="views-left-30">',
          '#suffix' => '</div>',
          '#title' => $this->t('Type'),
          '#type' => 'radios',
          '#options' => array(
            'none' => $this->t('No menu entry'),
            'normal' => $this->t('Normal menu entry'),
            'tab' => $this->t('Menu tab'),
            'default tab' => $this->t('Default menu tab')
          ),
          '#default_value' => $menu['type'],
        );

        $form['menu']['title'] = array(
          '#prefix' => '<div class="views-left-50">',
          '#title' => $this->t('Menu link title'),
          '#type' => 'textfield',
          '#default_value' => $menu['title'],
          '#states' => array(
            'visible' => array(
              array(
                ':input[name="menu[type]"]' => array('value' => 'normal'),
              ),
              array(
                ':input[name="menu[type]"]' => array('value' => 'tab'),
              ),
              array(
                ':input[name="menu[type]"]' => array('value' => 'default tab'),
              ),
            ),
          ),
        );
        $form['menu']['description'] = array(
          '#title' => $this->t('Description'),
          '#type' => 'textfield',
          '#default_value' => $menu['description'],
          '#description' => $this->t("Shown when hovering over the menu link."),
          '#states' => array(
            'visible' => array(
              array(
                ':input[name="menu[type]"]' => array('value' => 'normal'),
              ),
              array(
                ':input[name="menu[type]"]' => array('value' => 'tab'),
              ),
              array(
                ':input[name="menu[type]"]' => array('value' => 'default tab'),
              ),
            ),
          ),
        );

        // Only display the parent selector if Menu UI module is enabled.
        $menu_parent = $menu['menu_name'] . ':' . $menu['parent'];
        if (\Drupal::moduleHandler()->moduleExists('menu_ui')) {
          $form['menu']['parent'] = \Drupal::service('menu.parent_form_selector')->parentSelectElement($menu_parent);
          $form['menu']['parent'] += array(
            '#title' => $this->t('Parent'),
            '#description' => $this->t('The maximum depth for a link and all its children is fixed. Some menu links may not be available as parents if selecting them would exceed this limit.'),
            '#attributes' => array('class' => array('menu-title-select')),
            '#states' => array(
              'visible' => array(
                array(
                  ':input[name="menu[type]"]' => array('value' => 'normal'),
                ),
                array(
                  ':input[name="menu[type]"]' => array('value' => 'tab'),
                ),
              ),
            ),
          );
        }
        else {
          $form['menu']['parent'] = array(
            '#type' => 'value',
            '#value' => $menu_parent,
          );
          $form['menu']['markup'] = array(
            '#markup' => $this->t('Menu selection requires the activation of Menu UI module.'),
          );
        }
        $form['menu']['weight'] = array(
          '#title' => $this->t('Weight'),
          '#type' => 'textfield',
          '#default_value' => isset($menu['weight']) ? $menu['weight'] : 0,
          '#description' => $this->t('In the menu, the heavier links will sink and the lighter links will be positioned nearer the top.'),
          '#states' => array(
            'visible' => array(
              array(
                ':input[name="menu[type]"]' => array('value' => 'normal'),
              ),
              array(
                ':input[name="menu[type]"]' => array('value' => 'tab'),
              ),
              array(
                ':input[name="menu[type]"]' => array('value' => 'default tab'),
              ),
            ),
          ),
        );
        $form['menu']['context'] = array(
          '#title' => $this->t('Context'),
          '#suffix' => '</div>',
          '#type' => 'checkbox',
          '#default_value' => !empty($menu['context']),
          '#description' => $this->t('Displays the link in contextual links'),
          '#states' => array(
            'visible' => array(
              ':input[name="menu[type]"]' => array('value' => 'tab'),
            ),
          ),
        );
        break;
      case 'tab_options':
        $form['#title'] .= $this->t('Default tab options');
        $tab_options = $this->getOption('tab_options');
        if (empty($tab_options)) {
          $tab_options = array('type' => 'none', 'title' => '', 'weight' => 0);
        }

        $form['tab_markup'] = array(
          '#markup' => '<div class="form-item description">' . $this->t('When providing a menu item as a tab, Drupal needs to know what the parent menu item of that tab will be. Sometimes the parent will already exist, but other times you will need to have one created. The path of a parent item will always be the same path with the last part left off. i.e, if the path to this view is <em>foo/bar/baz</em>, the parent path would be <em>foo/bar</em>.') . '</div>',
        );

        $form['tab_options'] = array(
          '#prefix' => '<div class="clearfix">',
          '#suffix' => '</div>',
          '#tree' => TRUE,
        );
        $form['tab_options']['type'] = array(
          '#prefix' => '<div class="views-left-25">',
          '#suffix' => '</div>',
          '#title' => $this->t('Parent menu item'),
          '#type' => 'radios',
          '#options' => array('none' => $this->t('Already exists'), 'normal' => $this->t('Normal menu item'), 'tab' => $this->t('Menu tab')),
          '#default_value' => $tab_options['type'],
        );
        $form['tab_options']['title'] = array(
          '#prefix' => '<div class="views-left-75">',
          '#title' => $this->t('Title'),
          '#type' => 'textfield',
          '#default_value' => $tab_options['title'],
          '#description' => $this->t('If creating a parent menu item, enter the title of the item.'),
          '#states' => array(
            'visible' => array(
              array(
                ':input[name="tab_options[type]"]' => array('value' => 'normal'),
              ),
              array(
                ':input[name="tab_options[type]"]' => array('value' => 'tab'),
              ),
            ),
          ),
        );
        $form['tab_options']['description'] = array(
          '#title' => $this->t('Description'),
          '#type' => 'textfield',
          '#default_value' => $tab_options['description'],
          '#description' => $this->t('If creating a parent menu item, enter the description of the item.'),
          '#states' => array(
            'visible' => array(
              array(
                ':input[name="tab_options[type]"]' => array('value' => 'normal'),
              ),
              array(
                ':input[name="tab_options[type]"]' => array('value' => 'tab'),
              ),
            ),
          ),
        );
        // Only display the menu selector if Menu UI module is enabled.
        if (\Drupal::moduleHandler()->moduleExists('menu_ui')) {
          $form['tab_options']['menu_name'] = array(
            '#title' => $this->t('Menu'),
            '#type' => 'select',
            '#options' => menu_ui_get_menus(),
            '#default_value' => $tab_options['menu_name'],
            '#description' => $this->t('Insert item into an available menu.'),
            '#states' => array(
              'visible' => array(
                ':input[name="tab_options[type]"]' => array('value' => 'normal'),
              ),
            ),
          );
        }
        else {
          $form['tab_options']['menu_name'] = array(
            '#type' => 'value',
            '#value' => $tab_options['menu_name'],
          );
          $form['tab_options']['markup'] = array(
            '#markup' => $this->t('Menu selection requires the activation of Menu UI module.'),
          );
        }
        $form['tab_options']['weight'] = array(
          '#suffix' => '</div>',
          '#title' => $this->t('Tab weight'),
          '#type' => 'textfield',
          '#default_value' => $tab_options['weight'],
          '#size' => 5,
          '#description' => $this->t('If the parent menu item is a tab, enter the weight of the tab. Heavier tabs will sink and the lighter tabs will be positioned nearer to the first menu item.'),
          '#states' => array(
            'visible' => array(
              ':input[name="tab_options[type]"]' => array('value' => 'tab'),
            ),
          ),
        );
        break;
    }
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\callbackPluginBase::validateOptionsForm().
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    parent::validateOptionsForm($form, $form_state);

    if ($form_state->get('section') == 'menu') {
      $path = $this->getOption('path');
      $menu_type = $form_state->getValue(array('menu', 'type'));
      if ($menu_type == 'normal' && strpos($path, '%') !== FALSE) {
        $form_state->setError($form['menu']['type'], $this->t('Views cannot create normal menu items for paths with a % in them.'));
      }

      if ($menu_type == 'default tab' || $menu_type == 'tab') {
        $bits = explode('/', $path);
        $last = array_pop($bits);
        if ($last == '%') {
          $form_state->setError($form['menu']['type'], $this->t('A display whose path ends with a % cannot be a tab.'));
        }
      }

      if ($menu_type != 'none' && $form_state->isValueEmpty(array('menu', 'title'))) {
        $form_state->setError($form['menu']['title'], $this->t('Title is required for this menu type.'));
      }
    }
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\callbackPluginBase::submitOptionsForm().
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);

    switch ($form_state->get('section')) {
      case 'menu':
        $menu = $form_state->getValue('menu');
        list($menu['menu_name'], $menu['parent']) = explode(':', $menu['parent'], 2);
        $this->setOption('menu', $menu);
        // send ajax form to options page if we use it.
        if ($form_state->getValue(array('menu', 'type')) == 'default tab') {
          $form_state->get('view')->addFormToStack('display', $this->display['id'], 'tab_options');
        }
        break;
      case 'tab_options':
        $this->setOption('tab_options', $form_state->getValue('tab_options'));
        break;
    }
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::validate().
   */
  public function validate() {
    $errors = parent::validate();

    $menu = $this->getOption('menu');
    if (!empty($menu['type']) && $menu['type'] != 'none' && empty($menu['title'])) {
      $errors[] = $this->t('Display @display is set to use a menu but the menu link text is not set.', array('@display' => $this->display['display_title']));
    }

    if ($menu['type'] == 'default tab') {
      $tab_options = $this->getOption('tab_options');
      if (!empty($tab_options['type']) && $tab_options['type'] != 'none' && empty($tab_options['title'])) {
        $errors[] = $this->t('Display @display is set to use a parent menu but the parent menu link text is not set.', array('@display' => $this->display['display_title']));
      }
    }

    return $errors;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::getArgumentText().
   */
  public function getArgumentText() {
    return array(
      'filter value not present' => $this->t('When the filter value is <em>NOT</em> in the URL'),
      'filter value present' => $this->t('When the filter value <em>IS</em> in the URL or a default is provided'),
      'description' => $this->t('The contextual filter values is provided by the URL.'),
    );
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::getPagerText().
   */
  public function getPagerText() {
    return array(
      'items per page title' => $this->t('Items per page'),
      'items per page description' => $this->t('The number of items to display per page. Enter 0 for no limit.')
    );
  }

}
