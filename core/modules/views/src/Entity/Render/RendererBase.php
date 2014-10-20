<?php

/**
 * @file
 * Contains \Drupal\views\Entity\Render\RendererBase.
 */

namespace Drupal\views\Entity\Render;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Defines a base class for entity row renderers.
 */
abstract class RendererBase {

  /**
   * The view executable wrapping the view storage entity.
   *
   * @var \Drupal\views\ViewExecutable
   */
  public $view = NULL;

  /**
   * The type of the entity being rendered.
   *
   * @var string
   */
  protected $entityType;

  /**
   * Contains an array of render arrays, one for each rendered entity.
   *
   * @var array
   */
  protected $build = array();

  /**
   * Constructs a renderer object.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The entity row being rendered.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   */
  public function __construct(ViewExecutable $view, EntityTypeInterface $entity_type) {
    $this->view = $view;
    $this->entityType = $entity_type;
  }

  /**
   * Alters the query if needed.
   *
   * @param \Drupal\views\Plugin\views\query\QueryPluginBase $query
   *   The query to alter.
   */
  public function query(QueryPluginBase $query) {
  }

  /**
   * Runs before each row is rendered.
   *
   * @param $result
   *   The full array of results from the query.
   */
  public function preRender(array $result) {
    $view_builder = $this->view->rowPlugin->entityManager->getViewBuilder($this->entityType->id());

    /** @var \Drupal\views\ResultRow $row */
    foreach ($result as $row) {
      $entity = $row->_entity;
      $entity->view = $this->view;
      $this->build[$entity->id()] = $view_builder->view($entity, $this->view->rowPlugin->options['view_mode'], $this->getLangcode($row));
    }
  }

  /**
   * Renders a row object.
   *
   * @param \Drupal\views\ResultRow $row
   *   A single row of the query result.
   *
   * @return array
   *   The renderable array of a single row.
   */
  public function render(ResultRow $row) {
    $entity_id = $row->_entity->id();
    return $this->build[$entity_id];
  }

}
