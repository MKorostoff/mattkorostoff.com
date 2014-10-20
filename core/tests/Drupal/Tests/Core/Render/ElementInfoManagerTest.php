<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Render\ElementInfoManagerTest.
 */

namespace Drupal\Tests\Core\Render;

use Drupal\Core\Render\ElementInfoManager;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Render\ElementInfoManager
 * @group Render
 */
class ElementInfoManagerTest extends UnitTestCase {

  /**
   * The class under test.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfo;

  /**
   * The cache backend to use.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cache;

  /**
   * The mocked module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   *
   * @covers ::__construct
   */
  protected function setUp() {
    $this->cache = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');
    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');

    $this->elementInfo = new ElementInfoManager(new \ArrayObject(), $this->cache, $this->moduleHandler);
  }

  /**
   * Tests the getInfo method.
   *
   * @covers ::getInfo
   * @covers ::buildInfo
   *
   * @dataProvider providerTestGetInfo
   */
  public function testGetInfo($type, $expected_info, $element_info, callable $alter_callback = NULL) {
    $this->moduleHandler->expects($this->once())
      ->method('invokeAll')
      ->with('element_info')
      ->will($this->returnValue($element_info));
    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('element_info', $this->anything())
      ->will($this->returnCallback($alter_callback ?: function($info) {
        return $info;
      }));

    $this->assertEquals($expected_info, $this->elementInfo->getInfo($type));
  }

  /**
   * Provides tests data for getInfo.
   *
   * @return array
   */
  public function providerTestGetInfo() {
    $data = array();
    // Provide an element and expect it is returned.
    $data[] = array(
      'page',
      array(
        '#type' => 'page',
        '#show_messages' => TRUE,
        '#theme' => 'page',
        '#defaults_loaded' => TRUE,
      ),
      array('page' => array(
        '#show_messages' => TRUE,
        '#theme' => 'page',
      )),
    );
    // Provide an element but request an non existent one.
    $data[] = array(
      'form',
      array(
        '#defaults_loaded' => TRUE,
      ),
      array('page' => array(
        '#show_messages' => TRUE,
        '#theme' => 'page',
      )),
    );
    // Provide an element and alter it to ensure it is altered.
    $data[] = array(
      'page',
      array(
        '#type' => 'page',
        '#show_messages' => TRUE,
        '#theme' => 'page',
        '#number' => 597219,
        '#defaults_loaded' => TRUE,
      ),
      array('page' => array(
        '#show_messages' => TRUE,
        '#theme' => 'page',
      )),
      function ($alter_name, array &$info) {
        $info['page']['#number'] = 597219;
      }
    );
    return $data;
  }

  /**
   * Tests the getInfo() method when render element plugins are used.
   *
   * @covers ::getInfo
   * @covers ::buildInfo
   *
   * @dataProvider providerTestGetInfoElementPlugin
   */
  public function testGetInfoElementPlugin($plugin_class, $expected_info) {
    $this->moduleHandler->expects($this->once())
      ->method('invokeAll')
      ->with('element_info')
      ->willReturn(array());
    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('element_info', $this->anything())
      ->will($this->returnArgument(0));

    $plugin = $this->getMock($plugin_class);
    $plugin->expects($this->once())
      ->method('getInfo')
      ->willReturn(array(
        '#show_messages' => TRUE,
        '#theme' => 'page',
      ));

    $element_info = $this->getMockBuilder('Drupal\Core\Render\ElementInfoManager')
      ->setConstructorArgs(array(new \ArrayObject(), $this->cache, $this->moduleHandler))
      ->setMethods(array('getDefinitions', 'createInstance'))
      ->getMock();
    $element_info->expects($this->once())
      ->method('createInstance')
      ->with('page')
      ->willReturn($plugin);
    $element_info->expects($this->once())
      ->method('getDefinitions')
      ->willReturn(array(
        'page' => array('class' => 'TestElementPlugin'),
      ));

    $this->assertEquals($expected_info, $element_info->getInfo('page'));
  }

  /**
   * Provides tests data for testGetInfoElementPlugin().
   *
   * @return array
   */
  public function providerTestGetInfoElementPlugin() {
    $data = array();
    $data[] = array(
      'Drupal\Core\Render\Element\ElementInterface',
      array(
        '#type' => 'page',
        '#show_messages' => TRUE,
        '#theme' => 'page',
        '#defaults_loaded' => TRUE,
      ),
    );

    $data[] = array(
      'Drupal\Core\Render\Element\FormElementInterface',
      array(
        '#type' => 'page',
        '#show_messages' => TRUE,
        '#theme' => 'page',
        '#input' => TRUE,
        '#value_callback' => array('TestElementPlugin', 'valueCallback'),
        '#defaults_loaded' => TRUE,
      ),
    );
    return $data;
  }

}
