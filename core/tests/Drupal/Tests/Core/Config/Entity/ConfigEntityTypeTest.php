<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Config\Entity\ConfigEntityTypeTest.
 */

namespace Drupal\Tests\Core\Config\Entity;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\Component\Utility\String;

/**
 * @coversDefaultClass \Drupal\Core\Config\Entity\ConfigEntityType
 * @group Config
 */
class ConfigEntityTypeTest extends UnitTestCase {

  /**
   * Sets up a ConfigEntityType object for a given set of values.
   *
   * @param array $definition
   *   An array of values to use for the ConfigEntityType.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   */
  protected function setUpConfigEntityType($definition) {
    if (!isset($definition['id'])) {
      $definition += array(
        'id' => 'example_config_entity_type',
      );
    }
    return new ConfigEntityType($definition);
  }

  /**
   * Tests that we get an exception when the length of the config prefix that is
   * returned by getConfigPrefix() exceeds the maximum defined prefix length.
   *
   * @covers ::getConfigPrefix()
   */
  public function testConfigPrefixLengthExceeds() {
    $message_text = 'The configuration file name prefix @config_prefix exceeds the maximum character limit of @max_char.';

    // A provider length of 24 and config_prefix length of 59 (+1 for the .)
    // results in a config length of 84, which is too long.
    $definition = array(
      'provider' => $this->randomMachineName(24),
      'config_prefix' => $this->randomMachineName(59),
    );
    $config_entity = $this->setUpConfigEntityType($definition);
    $this->setExpectedException('\Drupal\Core\Config\ConfigPrefixLengthException', String::format($message_text, array(
      '@config_prefix' => $definition['provider'] . '.' . $definition['config_prefix'],
      '@max_char' => ConfigEntityType::PREFIX_LENGTH,
    )));
    $this->assertEmpty($config_entity->getConfigPrefix());
  }

  /**
   * Tests that a valid config prefix returned by getConfigPrefix()
   * does not throw an exception and is formatted as expected.
   *
   * @covers ::getConfigPrefix()
   */
  public function testConfigPrefixLengthValid() {
    // A provider length of 24 and config_prefix length of 58 (+1 for the .)
    // results in a config length of 83, which is right at the limit.
    $definition = array(
      'provider' => $this->randomMachineName(24),
      'config_prefix' => $this->randomMachineName(58),
    );
    $config_entity = $this->setUpConfigEntityType($definition);
    $expected_prefix = $definition['provider'] . '.' . $definition['config_prefix'];
    $this->assertEquals($expected_prefix, $config_entity->getConfigPrefix());
  }

}
