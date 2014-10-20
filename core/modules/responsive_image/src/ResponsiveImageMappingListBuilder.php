<?php

/**
 * @file
 * Contains Drupal\responsive_image\ResponsiveImageMappingListBuilder.
 */

namespace Drupal\responsive_image;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of responsive image mappings.
 */
class ResponsiveImageMappingListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Label');
    $header['id'] = t('Machine name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $this->getLabel($entity);
    $row['id'] = $entity->id();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    $operations['duplicate'] = array(
      'title' => t('Duplicate'),
      'weight' => 15,
    ) + $entity->urlInfo('duplicate-form')->toArray();
    return $operations;
  }

}
