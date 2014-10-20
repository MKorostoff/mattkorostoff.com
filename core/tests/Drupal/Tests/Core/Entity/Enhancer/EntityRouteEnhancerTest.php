<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\Enhancer\EntityRouteEnhancerTest.
 */

namespace Drupal\Tests\Core\Entity\Enhancer;

use Drupal\Core\Entity\Enhancer\EntityRouteEnhancer;
use Drupal\Tests\UnitTestCase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\Core\Entity\Enhancer\EntityRouteEnhancer
 * @group Entity
 */
class EntityRouteEnhancerTest extends UnitTestCase {

  /**
   * Tests the enhancer method.
   *
   * @see \Drupal\Core\Entity\Enhancer\EntityRouteEnhancer::enhancer()
   */
  public function testEnhancer() {
    $controller_resolver = $this->getMock('Drupal\Core\Controller\ControllerResolverInterface');
    $entity_manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $form_builder = $this->getMock('Drupal\Core\Form\FormBuilderInterface');

    $route_enhancer = new EntityRouteEnhancer($controller_resolver, $entity_manager, $form_builder);

    // Set a controller to ensure it is not overridden.
    $request = new Request();
    $defaults = array();
    $defaults['_controller'] = 'Drupal\Tests\Core\Controller\TestController::content';
    $defaults['_entity_form'] = 'entity_test.default';
    $new_defaults = $route_enhancer->enhance($defaults, $request);
    $this->assertTrue(is_callable($new_defaults['_content']));
    $this->assertInstanceOf('\Drupal\Core\Entity\HtmlEntityFormController', $new_defaults['_content'][0]);
    $this->assertEquals($new_defaults['_content'][1], 'getContentResult');
    $this->assertEquals($defaults['_controller'], $new_defaults['_controller'], '_controller got overridden.');

    // Set _entity_form and ensure that the form is set.
    $defaults = array();
    $defaults['_entity_form'] = 'entity_test.default';
    $new_defaults = $route_enhancer->enhance($defaults, $request);
    $this->assertTrue(is_callable($new_defaults['_content']));
    $this->assertInstanceOf('\Drupal\Core\Entity\HtmlEntityFormController', $new_defaults['_content'][0]);
    $this->assertEquals($new_defaults['_content'][1], 'getContentResult');

    // Set _entity_list and ensure that the entity list controller is set.
    $defaults = array();
    $defaults['_entity_list'] = 'entity_test.default';
    $new_defaults = $route_enhancer->enhance($defaults, $request);
    $this->assertEquals('\Drupal\Core\Entity\Controller\EntityListController::listing', $new_defaults['_content'], 'The entity list controller was not set.');
    $this->assertEquals('entity_test.default', $new_defaults['entity_type']);
    $this->assertFalse(isset($new_defaults['_entity_list']));

    // Set _entity_view and ensure that the entity view controller is set.
    $defaults = array();
    $defaults['_entity_view'] = 'entity_test.full';
    $defaults['entity_test'] = 'Mock entity';
    $defaults = $route_enhancer->enhance($defaults, $request);
    $this->assertEquals('\Drupal\Core\Entity\Controller\EntityViewController::view', $defaults['_content'], 'The entity view controller was not set.');
    $this->assertEquals($defaults['_entity'], 'Mock entity');
    $this->assertEquals($defaults['view_mode'], 'full');
    $this->assertFalse(isset($defaults['_entity_view']));

    // Set _entity_view and ensure that the entity view controller is set using
    // a converter.
    $defaults = array();
    $defaults['_entity_view'] = 'entity_test.full';
    $defaults['foo'] = 'Mock entity';
    // Add a converter.
    $options['parameters']['foo'] = array('type' => 'entity:entity_test');
    // Set the route.
    $route = $this->getMockBuilder('Symfony\Component\Routing\Route')
      ->disableOriginalConstructor()
      ->getMock();

    $route->expects($this->any())
      ->method('getOptions')
      ->will($this->returnValue($options));

    $defaults[RouteObjectInterface::ROUTE_OBJECT] = $route;
    $defaults = $route_enhancer->enhance($defaults, $request);
    $this->assertEquals('\Drupal\Core\Entity\Controller\EntityViewController::view', $defaults['_content'], 'The entity view controller was not set.');
    $this->assertEquals($defaults['_entity'], 'Mock entity');
    $this->assertEquals($defaults['view_mode'], 'full');
    $this->assertFalse(isset($defaults['_entity_view']));

    // Set _entity_view without a view mode.
    $defaults = array();
    $defaults['_entity_view'] = 'entity_test';
    $defaults['entity_test'] = 'Mock entity';
    $defaults = $route_enhancer->enhance($defaults, $request);
    $this->assertEquals('\Drupal\Core\Entity\Controller\EntityViewController::view', $defaults['_content'], 'The entity view controller was not set.');
    $this->assertEquals($defaults['_entity'], 'Mock entity');
    $this->assertTrue(empty($defaults['view_mode']));
    $this->assertFalse(isset($defaults['_entity_view']));
  }

}
