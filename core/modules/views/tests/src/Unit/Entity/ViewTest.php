<?php

/**
 * @file
 * Contains \Drupal\Tests\views\Unit\Entity\ViewTest.
 */

namespace Drupal\Tests\views\Unit\Entity {

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityType;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Entity\View;

/**
 * @coversDefaultClass \Drupal\views\Entity\View
 * @group views
 */
class ViewTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {

    // Setup the entity manager.
    $entity_definition = new EntityType(array('id' => 'view'));
    $entity_manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $entity_manager->expects($this->any())
      ->method('getDefinition')
      ->will($this->returnValue($entity_definition));
    $container_builder = new ContainerBuilder();
    $container_builder->set('entity.manager', $entity_manager);

    // Setup the string translation.
    $string_translation = $this->getStringTranslationStub();
    $container_builder->set('string_translation', $string_translation);
    \Drupal::setContainer($container_builder);
  }

  /**
   * Tests calculating dependencies.
   *
   * @covers ::calculateDependencies
   * @dataProvider calculateDependenciesProvider
   */
  public function testCalculateDependencies($values, $deps) {
    $view = new TestView($values, 'view');
    $this->assertEquals(array('module' => $deps), $view->calculateDependencies());
  }

  public function calculateDependenciesProvider(){
    $handler['display']['default']['provider'] = 'block';
    $handler['display']['default']['display_options']['fields']['example']['dependencies'] = array();
    $handler['display']['default']['display_options']['fields']['example2']['dependencies']['module'] = array('views', 'field');
    $handler['display']['default']['display_options']['fields']['example3']['dependencies']['module'] = array('views', 'image');

    $plugin['display']['default']['display_options']['access']['options']['dependencies'] = array();
    $plugin['display']['default']['display_options']['row']['options']['dependencies']['module'] = array('views', 'field');
    $plugin['display']['default']['display_options']['style']['options']['dependencies']['module'] = array('views', 'image');

    return array(
      array(array(), array('node', 'views')),
      array($handler, array('block', 'field', 'image', 'node', 'views')),
      array($plugin, array('field', 'image', 'node', 'views')),
    );
  }
}

class TestView extends View {

  /**
   * {@inheritdoc}
   */
  protected function drupalGetSchema($table = NULL, $rebuild = FALSE) {
    $result = array();
    if ($table == 'node') {
      $result['module'] = 'node';
    }
    return $result;
  }

}

}
