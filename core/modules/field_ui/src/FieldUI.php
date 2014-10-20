<?php

/**
 * @file
 * Contains \Drupal\field_ui\FieldUI.
 */

namespace Drupal\field_ui;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;

/**
 * Static service container wrapper for Field UI.
 */
class FieldUI {

  /**
   * Returns the route info for the field overview of a given entity bundle.
   *
   * @param string $entity_type_id
   *   An entity type.
   * @param string $bundle
   *   The entity bundle.
   *
   * @return \Drupal\Core\Url
   *   A URL object.
   */
  public static function getOverviewRouteInfo($entity_type_id, $bundle) {
    $entity_type = \Drupal::entityManager()->getDefinition($entity_type_id);
    if ($entity_type->get('field_ui_base_route')) {
      return new Url("field_ui.overview_$entity_type_id", array(
        $entity_type->getBundleEntityType() => $bundle,
      ));
    }
  }

  /**
   * Returns the next redirect path in a multipage sequence.
   *
   * @param array $destinations
   *   An array of destinations to redirect to.
   *
   * @return \Drupal\Core\Url
   *   The next destination to redirect to.
   */
  public static function getNextDestination(array $destinations) {
    $next_destination = array_shift($destinations);
    if (is_array($next_destination)) {
      $next_destination['options']['query']['destinations'] = $destinations;
      $next_destination += array(
        'route_parameters' => array(),
      );
      $next_destination = Url::fromRoute($next_destination['route_name'], $next_destination['route_parameters'], $next_destination['options']);
    }
    else {
      $options = UrlHelper::parse($next_destination);
      if ($destinations) {
        $options['query']['destinations'] = $destinations;
      }
      // Redirect to any given path within the same domain.
      $next_destination = Url::fromUri('base://' . $options['path']);
    }
    return $next_destination;
  }

}
