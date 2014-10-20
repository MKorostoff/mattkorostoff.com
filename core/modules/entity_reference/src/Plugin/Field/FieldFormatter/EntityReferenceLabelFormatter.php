<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter.
 */

namespace Drupal\entity_reference\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\String;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity reference label' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_label",
 *   label = @Translation("Label"),
 *   description = @Translation("Display the label of the referenced entities."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceLabelFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'link' => TRUE,
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['link'] = array(
      '#title' => t('Link label to the referenced entity'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('link'),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $summary[] = $this->getSetting('link') ? t('Link to the referenced entity') : t('No link');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      if (!$item->access) {
        // User doesn't have access to the referenced entity.
        continue;
      }
      /** @var $referenced_entity \Drupal\Core\Entity\EntityInterface */
      if ($referenced_entity = $item->entity) {
        $label = $referenced_entity->label();
        // If the link is to be displayed and the entity has a uri, display a
        // link.
        if ($this->getSetting('link') && $uri = $referenced_entity->urlInfo()) {
          $elements[$delta] = array(
            '#type' => 'link',
            '#title' => $label,
          ) + $uri->toRenderArray();

          if (!empty($item->_attributes)) {
            $elements[$delta]['#options'] += array('attributes' => array());
            $elements[$delta]['#options']['attributes'] += $item->_attributes;
            // Unset field item attributes since they have been included in the
            // formatter output and shouldn't be rendered in the field template.
            unset($item->_attributes);
          }
        }
        else {
          $elements[$delta] = array('#markup' => String::checkPlain($label));
        }
        $elements[$delta]['#cache']['tags'] = $referenced_entity->getCacheTag();
      }
    }

    return $elements;
  }

}
