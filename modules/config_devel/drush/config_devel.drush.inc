<?php

/**
 * @file
 * Configuration development module drush integration.
 */

use Drupal\Core\Config\InstallStorage;

/**
 * Implements hook_drush_command().
 */
function config_devel_drush_command() {
  $items = array();

  $description = '';
  $description .= "Write back configuration to module's config/install directory." . "\n";
  $description .= "List which configuration settings you want to export in the" . "\n";
  $description .= "module's info file by listing them under 'config_devel', as shown below:"  . "\n";
  $description .= "\n";
  $description .= "config_devel:"  . "\n";
  $description .= "  - entity.view_display.node.article.default"  . "\n";
  $description .= "  - entity.view_display.node.article.teaser"  . "\n";
  $description .= "  - field.instance.node.article.body"  . "\n";

  $items['config-devel-export-module'] = array(
    'description' => $description,
    'arguments' => array(
      'module' => 'Module machine name.',
    ),
    'options' => array(
    ),
    'examples' => array(
      'drush config-devel-export-module MODULE_NAME' => 'Write back configuration to the specified module, based on .info file.',
    ),
    'aliases' => array('cd-em'),
  );

  $items['config-devel-export-all'] = array(
    'description' => "Write back configuration to module's config/install directory",
    'arguments' => array(
      'module' => 'Module machine name.',
    ),
    'options' => array(
    ),
    'examples' => array(
      'drush config-devel-export-module MODULE_NAME' => 'Write back configuration to the specified module, based on .info file.',
    ),
    'aliases' => array('cd-em'),
  );

  return $items;
}


/**
 * Drush command callback.
 */
function drush_config_devel_export_module($module = NULL) {

  if ($module && \Drupal::moduleHandler()->moduleExists($module)) {
    $filename = drupal_get_path('module', $module) . '/' . $module .'.info.yml';
    $info = \Drupal::service('info_parser')->parse($filename);

    if (isset($info['config_devel'])) {
      foreach ($info['config_devel'] as $name) {
        $config = \Drupal::config($name);
        $config_path = drupal_get_path('module', $module) . '/' . InstallStorage::CONFIG_INSTALL_DIRECTORY;
        $file_names = array($config_path . '/' . $name . '.yml');
        \Drupal::service('config_devel.writeback_subscriber')->writeBackConfig($config, $file_names);
      }
    }
  }
}
