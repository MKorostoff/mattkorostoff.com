<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Cache\CacheTest.
 */

namespace Drupal\Tests\Core\Cache;

use Drupal\Core\Cache\Cache;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Cache\Cache
 * @group Cache
 */
class CacheTest extends UnitTestCase {

  /**
   * Provides a list of cache tags arrays.
   *
   * @return array
   */
  public function validateTagsProvider() {
    return [
      [[], FALSE],
      [['foo'], FALSE],
      [['foo', 'bar'], FALSE],
      [['foo', 'bar', 'llama:2001988', 'baz', 'llama:14031991'], FALSE],
      [[FALSE], 'Cache tags must be strings, boolean given.'],
      [[TRUE], 'Cache tags must be strings, boolean given.'],
      [['foo', FALSE], 'Cache tags must be strings, boolean given.'],
      [[NULL], 'Cache tags must be strings, NULL given.'],
      [['foo', NULL], 'Cache tags must be strings, NULL given.'],
      [[1337], 'Cache tags must be strings, integer given.'],
      [['foo', 1337], 'Cache tags must be strings, integer given.'],
      [[3.14], 'Cache tags must be strings, double given.'],
      [['foo', 3.14], 'Cache tags must be strings, double given.'],
      [[[]], 'Cache tags must be strings, array given.'],
      [['foo', []], 'Cache tags must be strings, array given.'],
      [['foo', ['bar']], 'Cache tags must be strings, array given.'],
      [[new \stdClass()], 'Cache tags must be strings, object given.'],
      [['foo', new \stdClass()], 'Cache tags must be strings, object given.'],
    ];
  }

  /**
   * @covers validateTags
   *
   * @dataProvider validateTagsProvider
   */
  public function testValidateTags(array $tags, $expected_exception_message) {
    if ($expected_exception_message !== FALSE) {
      $this->setExpectedException('LogicException', $expected_exception_message);
    }
    Cache::validateTags($tags);
  }

  /**
   * Provides a list of pairs of cache tags arrays to be merged.
   *
   * @return array
   */
  public function mergeTagsProvider() {
    return [
      [[], [], []],
      [['bar'], ['foo'], ['bar', 'foo']],
      [['foo'], ['bar'], ['bar', 'foo']],
      [['foo'], ['bar', 'foo'], ['bar', 'foo']],
      [['foo'], ['foo', 'bar'], ['bar', 'foo']],
      [['bar', 'foo'], ['foo', 'bar'], ['bar', 'foo']],
      [['foo', 'bar'], ['foo', 'bar'], ['bar', 'foo']],
    ];
  }

  /**
   * @covers mergeTags
   *
   * @dataProvider mergeTagsProvider
   */
  public function testMergeTags(array $a, array $b, array $expected) {
    $this->assertEquals($expected, Cache::mergeTags($a, $b));
  }

  /**
   * Provides a list of pairs of (prefix, suffixes) to build cache tags from.
   *
   * @return array
   */
  public function buildTagsProvider() {
    return [
      ['node', [1], ['node:1']],
      ['node', [1, 2, 3], ['node:1', 'node:2', 'node:3']],
      ['node', [3, 2, 1], ['node:3', 'node:2', 'node:1']],
      ['node', [NULL], ['node:']],
      ['node', [TRUE, FALSE], ['node:1', 'node:']],
      ['node', ['a', 'z', 'b'], ['node:a', 'node:z', 'node:b']],
      // No suffixes, no cache tags.
      ['', [], []],
      ['node', [], []],
      // Only suffix values matter, not keys: verify that expectation.
      ['node', [5 => 145, 4545 => 3], ['node:145', 'node:3']],
      ['node', [5 => TRUE], ['node:1']],
      ['node', [5 => NULL], ['node:']],
      ['node', ['a' => NULL], ['node:']],
      ['node', ['a' => TRUE], ['node:1']],
    ];
  }

  /**
   * @covers buildTags
   *
   * @dataProvider buildTagsProvider
   */
  public function testBuildTags($prefix, array $suffixes, array $expected) {
    $this->assertEquals($expected, Cache::buildTags($prefix, $suffixes));
  }

  /**
   * @covers deleteTags
   *
   * @expectedException \LogicException
   * @expectedExceptionMessage Cache tags must be strings, array given.
   */
  public function testDeleteTags() {
    Cache::deleteTags(['node' => [2, 3, 5, 8, 13]]);
  }

  /**
   * @covers invalidateTags
   *
   * @expectedException \LogicException
   * @expectedExceptionMessage Cache tags must be strings, array given.
   */
  public function testInvalidateTags() {
    Cache::deleteTags(['node' => [2, 3, 5, 8, 13]]);
  }

}
