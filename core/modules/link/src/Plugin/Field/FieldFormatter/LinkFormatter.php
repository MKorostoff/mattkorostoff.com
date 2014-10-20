<?php

/**
 * @file
 * Contains \Drupal\link\Plugin\field\formatter\LinkFormatter.
 */

namespace Drupal\link\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\String;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\link\LinkItemInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Plugin implementation of the 'link' formatter.
 *
 * @FieldFormatter(
 *   id = "link",
 *   label = @Translation("Link"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class LinkFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'trim_length' => '80',
      'url_only' => '',
      'url_plain' => '',
      'rel' => '',
      'target' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['trim_length'] = array(
      '#type' => 'number',
      '#title' => t('Trim link text length'),
      '#field_suffix' => t('characters'),
      '#default_value' => $this->getSetting('trim_length'),
      '#min' => 1,
      '#description' => t('Leave blank to allow unlimited link text lengths.'),
    );
    $elements['url_only'] = array(
      '#type' => 'checkbox',
      '#title' => t('URL only'),
      '#default_value' => $this->getSetting('url_only'),
      '#access' => $this->getPluginId() == 'link',
    );
    $elements['url_plain'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show URL as plain text'),
      '#default_value' => $this->getSetting('url_plain'),
      '#access' => $this->getPluginId() == 'link',
      '#states' => array(
        'visible' => array(
          ':input[name*="url_only"]' => array('checked' => TRUE),
        ),
      ),
    );
    $elements['rel'] = array(
      '#type' => 'checkbox',
      '#title' => t('Add rel="nofollow" to links'),
      '#return_value' => 'nofollow',
      '#default_value' => $this->getSetting('rel'),
    );
    $elements['target'] = array(
      '#type' => 'checkbox',
      '#title' => t('Open link in new window'),
      '#return_value' => '_blank',
      '#default_value' => $this->getSetting('target'),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $settings = $this->getSettings();

    if (!empty($settings['trim_length'])) {
      $summary[] = t('Link text trimmed to @limit characters', array('@limit' => $settings['trim_length']));
    }
    else {
      $summary[] = t('Link text not trimmed');
    }
    if ($this->getPluginId() == 'link' && !empty($settings['url_only'])) {
      if (!empty($settings['url_plain'])) {
        $summary[] = t('Show URL only as plain-text');
      }
      else {
        $summary[] = t('Show URL only');
      }
    }
    if (!empty($settings['rel'])) {
      $summary[] = t('Add rel="@rel"', array('@rel' => $settings['rel']));
    }
    if (!empty($settings['target'])) {
      $summary[] = t('Open link in new window');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $element = array();
    $entity = $items->getEntity();
    $settings = $this->getSettings();

    foreach ($items as $delta => $item) {
      // By default use the full URL as the link text.
      $url = $this->buildUrl($item);
      $link_title = $url->toString();

      // If the title field value is available, use it for the link text.
      if (empty($settings['url_only']) && !empty($item->title)) {
        // Unsanitized token replacement here because $options['html'] is FALSE
        // by default in _l().
        $link_title = \Drupal::token()->replace($item->title, array($entity->getEntityTypeId() => $entity), array('sanitize' => FALSE, 'clear' => TRUE));
      }

      // Trim the link text to the desired length.
      if (!empty($settings['trim_length'])) {
        $link_title = truncate_utf8($link_title, $settings['trim_length'], FALSE, TRUE);
      }

      if (!empty($settings['url_only']) && !empty($settings['url_plain'])) {
        $element[$delta] = array(
          '#markup' => String::checkPlain($link_title),
        );

        if (!empty($item->_attributes)) {
          // Piggyback on the metadata attributes, which will be placed in the
          // field template wrapper, and set the URL value in a content
          // attribute.
          $item->_attributes += array('content' => $item->url);
        }
      }
      else {
        $element[$delta] = array(
          '#type' => 'link',
          '#title' => $link_title,
          '#options' => $url->getOptions(),
        );
        if ($url->isExternal()) {
          $element[$delta]['#href'] = $url->getUri();
        }
        else {
          $element[$delta]['#route_name'] = $url->getRouteName();
          $element[$delta]['#route_parameters'] = $url->getRouteParameters();
        }

        if (!empty($item->_attributes)) {
          $element[$delta]['#options'] += array ('attributes' => array());
          $element[$delta]['#options']['attributes'] += $item->_attributes;
          // Unset field item attributes since they have been included in the
          // formatter output and should not be rendered in the field template.
          unset($item->_attributes);
        }
      }
    }

    return $element;
  }

  /**
   * Builds the \Drupal\Core\Url object for a link field item.
   *
   * @param \Drupal\link\LinkItemInterface $item
   *   The link field item being rendered.
   *
   * @return \Drupal\Core\Url
   *   An Url object.
   */
  protected function buildUrl(LinkItemInterface $item) {
    $settings = $this->getSettings();
    $options = $item->options;

    // Add optional 'rel' attribute to link options.
    if (!empty($settings['rel'])) {
      $options['attributes']['rel'] = $settings['rel'];
    }
    // Add optional 'target' attribute to link options.
    if (!empty($settings['target'])) {
      $options['attributes']['target'] = $settings['target'];
    }

    if ($item->isExternal()) {
      $url = Url::fromUri($item->url, $options);
    }
    else {
      $url = Url::fromRoute($item->route_name, (array) $item->route_parameters, (array) $options);
    }

    return $url;
  }

}
