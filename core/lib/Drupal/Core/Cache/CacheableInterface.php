<?php
/**
 * @file
 * Contains \Drupal\Core\CacheableInterface
 */

namespace Drupal\Core\Cache;

/**
 * Defines an interface for objects which are potentially cacheable.
 *
 * @ingroup cache
 */
interface CacheableInterface {

  /**
   * The cache keys associated with this potentially cacheable object.
   *
   * Cache keys may either be static (just strings) or tokens (placeholders
   * that are converted to static keys by the @cache_contexts service, depending
   * depending on the request).
   *
   * @return array
   *   An array of strings or cache context tokens, used to generate a cache ID.
   *
   * @see \Drupal\Core\Cache\CacheContexts::convertTokensToKeys()
   */
  public function getCacheKeys();

  /**
   * The cache tags associated with this potentially cacheable object.
   *
   * @return array
   *  An array of cache tags.
   */
  public function getCacheTags();

  /**
   * The maximum age for which this object may be cached.
   *
   * @return int
   *   The maximum time in seconds that this object may be cached.
   */
  public function getCacheMaxAge();

  /**
   * Indicates whether this object is cacheable.
   *
   * @return bool
   *   Returns TRUE if the object is cacheable, FALSE otherwise.
   */
  public function isCacheable();

}
