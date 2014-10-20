<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\source\d6\File.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\source\d6;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 file source from database.
 *
 * @MigrateSource(
 *   id = "d6_file"
 * )
 */
class File extends DrupalSqlBase {

  /**
   * The file directory path.
   *
   * @var string
   */
  protected $filePath;

  /**
   * Flag for private or public file storage.
   *
   * @var boolean
   */
  protected $isPublic;

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('files', 'f')->fields('f', array(
      'fid',
      'uid',
      'filename',
      'filepath',
      'filemime',
      'filesize',
      'status',
      'timestamp',
    ));
    $query->orderBy('timestamp');
    return $query;
  }


  /**
   * {@inheritdoc}
   */
  protected function runQuery() {
    $conf_path = isset($this->configuration['conf_path']) ? $this->configuration['conf_path'] : 'sites/default';
    $this->filePath = $this->variableGet('file_directory_path', $conf_path . '/files') . '/';

    // FILE_DOWNLOADS_PUBLIC == 1 and FILE_DOWNLOADS_PRIVATE == 2.
    $this->isPublic = $this->variableGet('file_downloads', 1) == 1;
    return parent::runQuery();
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $row->setSourceProperty('file_directory_path', $this->filePath);
    $row->setSourceProperty('is_public', $this->isPublic);
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'fid' => $this->t('File ID'),
      'uid' => $this->t('The {users}.uid who added the file. If set to 0, this file was added by an anonymous user.'),
      'filename' => $this->t('File name'),
      'filepath' => $this->t('File path'),
      'filemime' => $this->t('File Mime Type'),
      'status' => $this->t('The published status of a file.'),
      'timestamp' => $this->t('The time that the file was added.'),
      'file_directory_path' => $this->t('The Drupal files path.'),
      'is_public' => $this->t('TRUE if the files directory is public otherwise FALSE.'),
    );
  }
  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['fid']['type'] = 'integer';
    return $ids;
  }

}
