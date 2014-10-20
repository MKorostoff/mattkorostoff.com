<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Plugin\field\formatter\EntityReferenceTaxonomyTermRssFormatter.
 */

namespace Drupal\taxonomy\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\entity_reference\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;

/**
 * Plugin implementation of the 'entity reference taxonomy term RSS' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_rss_category",
 *   label = @Translation("RSS category"),
 *   description = @Translation("Display reference to taxonomy term in RSS."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceTaxonomyTermRssFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();
    $entity = $items->getEntity();

    foreach ($items as $item) {
      $entity->rss_elements[] = array(
        'key' => 'category',
        'value' => $item->entity->label(),
        'attributes' => array(
          'domain' => $item->target_id ? \Drupal::url('entity.taxonomy_term.canonical', ['taxonomy_term' => $item->target_id], array('absolute' => TRUE)) : '',
        ),
      );
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // This formatter is only available for taxonomy terms.
    return $field_definition->getFieldStorageDefinition()->getSetting('target_type') == 'taxonomy_term';
  }

}
