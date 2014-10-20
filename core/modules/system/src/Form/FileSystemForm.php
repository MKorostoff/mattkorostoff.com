<?php

/**
 * @file
 * Contains \Drupal\system\Form\FileSystemForm.
 */

namespace Drupal\system\Form;

use Drupal\Component\Utility\String;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure file system settings for this site.
 */
class FileSystemForm extends ConfigFormBase {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Constructs a FileSystemForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, DateFormatter $date_formatter) {
    parent::__construct($config_factory);
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('config.factory'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'system_file_system_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('system.file');
    $form['file_public_path'] = array(
      '#type' => 'item',
      '#title' => t('Public file system path'),
      '#default_value' => PublicStream::basePath(),
      '#markup' => PublicStream::basePath(),
      '#description' => t('A local file system path where public files will be stored. This directory must exist and be writable by Drupal. This directory must be relative to the Drupal installation directory and be accessible over the web. This must be changed in settings.php'),
    );

    $form['file_private_path'] = array(
      '#type' => 'textfield',
      '#title' => t('Private file system path'),
      '#default_value' => $config->get('path.private'),
      '#maxlength' => 255,
      '#description' => t('An existing local file system path for storing private files. It should be writable by Drupal and not accessible over the web. See the online handbook for <a href="@handbook">more information about securing private files</a>.', array('@handbook' => 'http://drupal.org/documentation/modules/file')),
      '#after_build' => array('system_check_directory'),
    );

    $form['file_temporary_path'] = array(
      '#type' => 'textfield',
      '#title' => t('Temporary directory'),
      '#default_value' => $config->get('path.temporary'),
      '#maxlength' => 255,
      '#description' => t('A local file system path where temporary files will be stored. This directory should not be accessible over the web.'),
      '#after_build' => array('system_check_directory'),
    );
    // Any visible, writeable wrapper can potentially be used for the files
    // directory, including a remote file system that integrates with a CDN.
    foreach (file_get_stream_wrappers(STREAM_WRAPPERS_WRITE_VISIBLE) as $scheme => $info) {
      $options[$scheme] = String::checkPlain($info['description']);
    }

    if (!empty($options)) {
      $form['file_default_scheme'] = array(
        '#type' => 'radios',
        '#title' => t('Default download method'),
        '#default_value' => $config->get('default_scheme'),
        '#options' => $options,
        '#description' => t('This setting is used as the preferred download method. The use of public files is more efficient, but does not provide any access control.'),
      );
    }

    $intervals = array(0, 21600, 43200, 86400, 604800, 2419200, 7776000);
    $period = array_combine($intervals, array_map(array($this->dateFormatter, 'formatInterval'), $intervals));
    $period[0] = t('Never');
    $form['temporary_maximum_age'] = array(
      '#type' => 'select',
      '#title' => t('Delete orphaned files after'),
      '#default_value' => $config->get('temporary_maximum_age'),
      '#options' => $period,
      '#description' => t('Orphaned files are not referenced from any content but remain in the file system and may appear in administrative listings. <strong>Warning:</strong> If enabled, orphaned files will be permanently deleted and may not be recoverable.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('system.file')
      ->set('path.private', $form_state->getValue('file_private_path'))
      ->set('path.temporary', $form_state->getValue('file_temporary_path'))
      ->set('temporary_maximum_age', $form_state->getValue('temporary_maximum_age'));

    if ($form_state->hasValue('file_default_scheme')) {
      $config->set('default_scheme', $form_state->getValue('file_default_scheme'));
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
