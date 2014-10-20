<?php

/**
 * @file
 * Definition of Drupal\file\Plugin\views\filter\Status.
 */

namespace Drupal\file\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filter by file status.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("file_status")
 */
class Status extends InOperator {

  public function getValueOptions() {
    if (!isset($this->value_options)) {
      $this->value_options = _views_file_status();
    }
  }

}
