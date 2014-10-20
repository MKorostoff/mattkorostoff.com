<?php

/**
 * @file
 * Definition of Drupal\user\TempStoreFactory.
 */

namespace Drupal\user;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\KeyValueStore\DatabaseStorageExpirable;
use Drupal\Core\Lock\LockBackendInterface;

/**
 * Creates a key/value storage object for the current user or anonymous session.
 */
class TempStoreFactory {

  /**
   * The serialization class to use.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $serializer;

  /**
   * The connection object used for this data.
   *
   * @var \Drupal\Core\Database\Connection $connection
   */
  protected $connection;

  /**
   * The lock object used for this data.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface $lockBackend
   */
  protected $lockBackend;

  /**
   * The time to live for items in seconds.
   *
   * @var int
   */
  protected $expire;

  /**
   * Constructs a Drupal\user\TempStoreFactory object.
   *
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   The serialization class to use.
   * @param \Drupal\Core\Database\Connection $connection
   *   The connection object used for this data.
   * @param \Drupal\Core\Lock\LockBackendInterface $lockBackend
   *   The lock object used for this data.
   * @param int $expire
   *   The time to live for items, in seconds.
   */
  function __construct(SerializationInterface $serializer, Connection $connection, LockBackendInterface $lockBackend, $expire = 604800) {
    $this->serializer = $serializer;
    $this->connection = $connection;
    $this->lockBackend = $lockBackend;
    $this->expire = $expire;
  }

  /**
   * Creates a TempStore for the current user or anonymous session.
   *
   * @param string $collection
   *   The collection name to use for this key/value store. This is typically
   *   a shared namespace or module name, e.g. 'views', 'entity', etc.
   * @param mixed $owner
   *   (optional) The owner of this TempStore. By default, the TempStore is
   *   owned by the currently authenticated user, or by the active anonymous
   *   session if no user is logged in.
   *
   * @return \Drupal\user\TempStore
   *   An instance of the the key/value store.
   */
  function get($collection, $owner = NULL) {
    // Use the currently authenticated user ID or the active user ID unless
    // the owner is overridden.
    if (!isset($owner)) {
      $owner = \Drupal::currentUser()->id() ?: session_id();
    }

    // Store the data for this collection in the database.
    $storage = new DatabaseStorageExpirable($collection, $this->serializer, $this->connection);
    return new TempStore($storage, $this->lockBackend, $owner, $this->expire);
  }

}
