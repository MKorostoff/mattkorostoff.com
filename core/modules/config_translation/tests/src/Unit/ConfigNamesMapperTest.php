<?php

/**
 * @file
 * Contains \Drupal\Tests\config_translation\Unit\ConfigNamesMapperTest.
 */

namespace Drupal\Tests\config_translation\Unit;

use Drupal\config_translation\ConfigNamesMapper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\Language;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the functionality provided by the configuration names mapper.
 *
 * @group config_translation
 */
class ConfigNamesMapperTest extends UnitTestCase {

  /**
   * The plugin definition of the test mapper.
   *
   * @var array
   */
  protected $pluginDefinition;

  /**
   * The configuration names mapper to test.
   *
   * @see \Drupal\config_translation\ConfigNamesMapper
   *
   * @var \Drupal\Tests\config_translation\Unit\TestConfigNamesMapper
   */
  protected $configNamesMapper;

  /**
   * The locale configuration manager.
   *
   * @var \Drupal\locale\LocaleConfigManager|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $localeConfigManager;

  /**
   * The locale configuration manager.
   *
   * @var \Drupal\locale\LocaleConfigManager|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $typedConfigManager;

  /**
   * The configuration mapper manager.
   *
   * @var \Drupal\config_translation\ConfigMapperManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $configMapperManager;

  /**
   * The base route used for testing.
   *
   * @var \Symfony\Component\Routing\Route
   */
  protected $baseRoute;

  /**
   * The route provider used for testing.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $routeProvider;

  protected function setUp() {
    $this->routeProvider = $this->getMock('Drupal\Core\Routing\RouteProviderInterface');

    $this->pluginDefinition = array(
      'class' => '\Drupal\config_translation\ConfigNamesMapper',
      'base_route_name' => 'system.site_information_settings',
      'title' => 'System information',
      'names' => array('system.site'),
      'weight' => 42,
    );

    $this->typedConfigManager = $this->getMock('Drupal\Core\Config\TypedConfigManagerInterface');

    $this->localeConfigManager = $this->getMockBuilder('Drupal\locale\LocaleConfigManager')
      ->disableOriginalConstructor()
      ->getMock();

    $this->configMapperManager = $this->getMock('Drupal\config_translation\ConfigMapperManagerInterface');

    $this->baseRoute = new Route('/admin/config/system/site-information');

    $this->routeProvider
      ->expects($this->any())
      ->method('getRouteByName')
      ->with('system.site_information_settings')
      ->will($this->returnValue($this->baseRoute));

    $this->configNamesMapper = new TestConfigNamesMapper(
      'system.site_information_settings',
      $this->pluginDefinition,
      $this->getConfigFactoryStub(),
      $this->typedConfigManager,
      $this->localeConfigManager,
      $this->configMapperManager,
      $this->routeProvider,
      $this->getStringTranslationStub()
    );
  }

  /**
   * Tests ConfigNamesMapper::getTitle().
   */
  public function testGetTitle() {
    $result = $this->configNamesMapper->getTitle();
    $this->assertSame($this->pluginDefinition['title'], $result);
  }

  /**
   * Tests ConfigNamesMapper::getBaseRouteName().
   */
  public function testGetBaseRouteName() {
    $result = $this->configNamesMapper->getBaseRouteName();
    $this->assertSame($this->pluginDefinition['base_route_name'], $result);
  }

  /**
   * Tests ConfigNamesMapper::getBaseRouteParameters().
   */
  public function testGetBaseRouteParameters() {
    $result = $this->configNamesMapper->getBaseRouteParameters();
    $this->assertSame(array(), $result);
  }

  /**
   * Tests ConfigNamesMapper::getBaseRoute().
   */
  public function testGetBaseRoute() {
    $result = $this->configNamesMapper->getBaseRoute();
    $this->assertSame($this->baseRoute, $result);
  }

  /**
   * Tests ConfigNamesMapper::getBasePath().
   */
  public function testGetBasePath() {
    $result = $this->configNamesMapper->getBasePath();
    $this->assertSame('/admin/config/system/site-information', $result);
  }

  /**
   * Tests ConfigNamesMapper::getOverviewRouteName().
   */
  public function testGetOverviewRouteName() {
    $result = $this->configNamesMapper->getOverviewRouteName();
    $expected = 'config_translation.item.overview.' . $this->pluginDefinition['base_route_name'];
    $this->assertSame($expected, $result);
  }

  /**
   * Tests ConfigNamesMapper::getOverviewRouteParameters().
   */
  public function testGetOverviewRouteParameters() {
    $result = $this->configNamesMapper->getOverviewRouteParameters();
    $this->assertSame(array(), $result);
  }

  /**
   * Tests ConfigNamesMapper::getOverviewRoute().
   */
  public function testGetOverviewRoute() {
    $expected = new Route('/admin/config/system/site-information/translate',
      array(
        '_content' => '\Drupal\config_translation\Controller\ConfigTranslationController::itemPage',
        'plugin_id' => 'system.site_information_settings',
      ),
      array(
        '_config_translation_overview_access' => 'TRUE',
      )
    );
    $result = $this->configNamesMapper->getOverviewRoute();
    $this->assertSame(serialize($expected), serialize($result));
  }

  /**
   * Tests ConfigNamesMapper::getOverviewPath().
   */
  public function testGetOverviewPath() {
    $result = $this->configNamesMapper->getOverviewPath();
    $this->assertSame('/admin/config/system/site-information/translate', $result);
  }

  /**
   * Tests ConfigNamesMapper::getAddRouteName().
   */
  public function testGetAddRouteName() {
    $result = $this->configNamesMapper->getAddRouteName();
    $expected = 'config_translation.item.add.' . $this->pluginDefinition['base_route_name'];
    $this->assertSame($expected, $result);
  }

  /**
   * Tests ConfigNamesMapper::getAddRouteParameters().
   */
  public function testGetAddRouteParameters() {
    $request = Request::create('');
    $request->attributes->set('langcode', 'xx');
    $this->configNamesMapper->populateFromRequest($request);

    $expected = array('langcode' => 'xx');
    $result = $this->configNamesMapper->getAddRouteParameters();
    $this->assertSame($expected, $result);
  }

  /**
   * Tests ConfigNamesMapper::getAddRoute().
   */
  public function testGetAddRoute() {
    $expected = new Route('/admin/config/system/site-information/translate/{langcode}/add',
      array(
        '_form' => '\Drupal\config_translation\Form\ConfigTranslationAddForm',
        'plugin_id' => 'system.site_information_settings',
      ),
      array(
        '_config_translation_form_access' => 'TRUE',
      )
    );
    $result = $this->configNamesMapper->getAddRoute();
    $this->assertSame(serialize($expected), serialize($result));
  }

  /**
   * Tests ConfigNamesMapper::getEditRouteName().
   */
  public function testGetEditRouteName() {
    $result = $this->configNamesMapper->getEditRouteName();
    $expected = 'config_translation.item.edit.' . $this->pluginDefinition['base_route_name'];
    $this->assertSame($expected, $result);
  }

  /**
   * Tests ConfigNamesMapper::getEditRouteParameters().
   */
  public function testGetEditRouteParameters() {
    $request = Request::create('');
    $request->attributes->set('langcode', 'xx');
    $this->configNamesMapper->populateFromRequest($request);

    $expected = array('langcode' => 'xx');
    $result = $this->configNamesMapper->getEditRouteParameters();
    $this->assertSame($expected, $result);
  }

  /**
   * Tests ConfigNamesMapper::getEditRoute().
   */
  public function testGetEditRoute() {
    $expected = new Route('/admin/config/system/site-information/translate/{langcode}/edit',
      array(
        '_form' => '\Drupal\config_translation\Form\ConfigTranslationEditForm',
        'plugin_id' => 'system.site_information_settings',
      ),
      array(
        '_config_translation_form_access' => 'TRUE',
      )
    );
    $result = $this->configNamesMapper->getEditRoute();
    $this->assertSame(serialize($expected), serialize($result));
  }

  /**
   * Tests ConfigNamesMapper::getDeleteRouteName().
   */
  public function testGetDeleteRouteName() {
    $result = $this->configNamesMapper->getDeleteRouteName();
    $expected = 'config_translation.item.delete.' . $this->pluginDefinition['base_route_name'];
    $this->assertSame($expected, $result);
  }

  /**
   * Tests ConfigNamesMapper::getDeleteRouteParameters().
   */
  public function testGetDeleteRouteParameters() {
    $request = Request::create('');
    $request->attributes->set('langcode', 'xx');
    $this->configNamesMapper->populateFromRequest($request);

    $expected = array('langcode' => 'xx');    $result = $this->configNamesMapper->getDeleteRouteParameters();
    $this->assertSame($expected, $result);
  }

  /**
   * Tests ConfigNamesMapper::getRoute().
   */
  public function testGetDeleteRoute() {
    $expected = new Route('/admin/config/system/site-information/translate/{langcode}/delete',
      array(
        '_form' => '\Drupal\config_translation\Form\ConfigTranslationDeleteForm',
        'plugin_id' => 'system.site_information_settings',
      ),
      array(
        '_config_translation_form_access' => 'TRUE',
      )
    );
    $result = $this->configNamesMapper->getDeleteRoute();
    $this->assertSame(serialize($expected), serialize($result));
  }

  /**
   * Tests ConfigNamesMapper::getConfigNames().
   */
  public function testGetConfigNames() {
    $result = $this->configNamesMapper->getConfigNames();
    $this->assertSame($this->pluginDefinition['names'], $result);
  }

  /**
   * Tests ConfigNamesMapper::addConfigName().
   */
  public function testAddConfigName() {
    $names = $this->configNamesMapper->getConfigNames();
    $this->configNamesMapper->addConfigName('test');
    $names[] = 'test';
    $result = $this->configNamesMapper->getConfigNames();
    $this->assertSame($names, $result);
  }

  /**
   * Tests ConfigNamesMapper::getWeight().
   */
  public function testGetWeight() {
    $result = $this->configNamesMapper->getWeight();
    $this->assertSame($this->pluginDefinition['weight'], $result);
  }

  /**
   * Tests ConfigNamesMapper::populateFromRequest().
   */
  public function testPopulateFromRequest() {
    // Make sure the language code is not set initially.
    $this->assertSame(NULL, $this->configNamesMapper->getInternalLangcode());

    // Test that an empty request does not set the language code.
    $request = Request::create('');
    $this->configNamesMapper->populateFromRequest($request);
    $this->assertSame(NULL, $this->configNamesMapper->getInternalLangcode());

    // Test that a request with a 'langcode' attribute sets the language code.
    $request->attributes->set('langcode', 'xx');
    $this->configNamesMapper->populateFromRequest($request);
    $this->assertSame('xx', $this->configNamesMapper->getInternalLangcode());

    // Test that the language code gets unset with the wrong request.
    $request->attributes->remove('langcode');
    $this->configNamesMapper->populateFromRequest($request);
    $this->assertSame(NULL, $this->configNamesMapper->getInternalLangcode());
  }

  /**
   * Tests ConfigNamesMapper::getTypeLabel().
   */
  public function testGetTypeLabel() {
    $result = $this->configNamesMapper->getTypeLabel();
    $this->assertSame($this->pluginDefinition['title'], $result);
  }

  /**
   * Tests ConfigNamesMapper::getLangcode().
   */
  public function testGetLangcode() {
    // Test that the getLangcode() falls back to 'en', if no explicit language
    // code is provided.
    $config_factory = $this->getConfigFactoryStub(array(
      'system.site' => array('key' => 'value'),
    ));
    $this->configNamesMapper->setConfigFactory($config_factory);
    $result = $this->configNamesMapper->getLangcode();
    $this->assertSame('en', $result);

    // Test that getLangcode picks up the language code provided by the
    // configuration.
    $config_factory = $this->getConfigFactoryStub(array(
      'system.site' => array('langcode' => 'xx'),
    ));
    $this->configNamesMapper->setConfigFactory($config_factory);
    $result = $this->configNamesMapper->getLangcode();
    $this->assertSame('xx', $result);

    // Test that getLangcode() works for multiple configuration names.
    $this->configNamesMapper->addConfigName('system.maintenance');
    $config_factory = $this->getConfigFactoryStub(array(
      'system.site' => array('langcode' => 'xx'),
      'system.maintenance' => array('langcode' => 'xx'),
    ));
    $this->configNamesMapper->setConfigFactory($config_factory);
    $result = $this->configNamesMapper->getLangcode();
    $this->assertSame('xx', $result);

    // Test that getLangcode() throws an exception when different language codes
    // are given.
    $config_factory = $this->getConfigFactoryStub(array(
      'system.site' => array('langcode' => 'xx'),
      'system.maintenance' => array('langcode' => 'yy'),
    ));
    $this->configNamesMapper->setConfigFactory($config_factory);
    try {
      $this->configNamesMapper->getLangcode();
      $this->fail();
    }
    catch (\RuntimeException $e) {}
  }

  // @todo Test ConfigNamesMapper::getLanguageWithFallback() once
  //   https://drupal.org/node/1862202 lands in core, because then we can
  //   remove the direct language_load() call.

  /**
   * Tests ConfigNamesMapper::getConfigData().
   */
  public function testGetConfigData() {
    $configs = array(
      'system.site' => array(
        'name' => 'Drupal',
        'slogan' => 'Come for the software, stay for the community!',
      ),
      'system.maintenance' => array(
        'enabled' => FALSE,
        'message' => '@site is currently under maintenance.',
      ),
      'system.rss' => array(
        'items' => array(
          'limit' => 10,
          'view_mode' => 'rss',
        ),
      ),
    );

    $this->configNamesMapper->setConfigNames(array_keys($configs));
    $config_factory = $this->getConfigFactoryStub($configs);
    $this->configNamesMapper->setConfigFactory($config_factory);

    $result = $this->configNamesMapper->getConfigData();
    $this->assertSame($configs, $result);
  }

  /**
   * Tests ConfigNamesMapper::hasSchema().
   *
   * @param array $mock_return_values
   *   An array of values that the mocked locale configuration manager should
   *   return for hasConfigSchema().
   * @param bool $expected
   *   The expected return value of ConfigNamesMapper::hasSchema().
   *
   * @dataProvider providerTestHasSchema
   */
  public function testHasSchema(array $mock_return_values, $expected) {
    // As the configuration names are arbitrary, simply use integers.
    $config_names = range(1, count($mock_return_values));
    $this->configNamesMapper->setConfigNames($config_names);

    $map = array();
    foreach ($config_names as $i => $config_name) {
      $map[] = array($config_name, $mock_return_values[$i]);
    }
    $this->typedConfigManager
      ->expects($this->any())
      ->method('hasConfigSchema')
      ->will($this->returnValueMap($map));

    $result = $this->configNamesMapper->hasSchema();
    $this->assertSame($expected, $result);
  }

  /**
   * Provides data for for ConfigMapperTest::testHasSchema().
   *
   * @return array
   *   An array of arrays, where each inner array has an array of values that
   *   the mocked locale configuration manager should return for
   *   hasConfigSchema() as the first value and the expected return value of
   *   ConfigNamesMapper::hasSchema() as the second value.
   */
  public function providerTestHasSchema() {
    return array(
      array(array(TRUE), TRUE),
      array(array(FALSE), FALSE),
      array(array(TRUE, TRUE, TRUE), TRUE),
      array(array(TRUE, FALSE, TRUE), FALSE),
    );
  }

  /**
   * Tests ConfigNamesMapper::hasTranslatable().
   *
   * @param array $mock_return_values
   *   An array of values that the mocked configuration mapper manager should
   *   return for hasTranslatable().
   * @param bool $expected
   *   The expected return value of ConfigNamesMapper::hasTranslatable().
   *
   * @dataProvider providerTestHasTranslatable
   */
  public function testHasTranslatable(array $mock_return_values, $expected) {
    // As the configuration names are arbitrary, simply use integers.
    $config_names = range(1, count($mock_return_values));
    $this->configNamesMapper->setConfigNames($config_names);

    $map = array();
    foreach ($config_names as $i => $config_name) {
      $map[] = array($config_name, $mock_return_values[$i]);
    }
    $this->configMapperManager
      ->expects($this->any())
      ->method('hasTranslatable')
      ->will($this->returnValueMap($map));

    $result = $this->configNamesMapper->hasTranslatable();
    $this->assertSame($expected, $result);
  }

  /**
   * Provides data for ConfigNamesMapperTest::testHasTranslatable().
   *
   * @return array
   *   An array of arrays, where each inner array has an array of values that
   *   the mocked configuration mapper manager should return for
   *   hasTranslatable() as the first value and the expected return value of
   *   ConfigNamesMapper::hasTranslatable() as the second value.
   */
  public function providerTestHasTranslatable() {
    return array(
      array(array(TRUE), TRUE),
      array(array(FALSE), FALSE),
      array(array(TRUE, TRUE, TRUE), TRUE),
      array(array(TRUE, FALSE, TRUE), FALSE),
    );
  }

  /**
   * Tests ConfigNamesMapper::hasTranslation().
   *
   * @param array $mock_return_values
   *   An array of values that the mocked configuration mapper manager should
   *   return for hasTranslation().
   * @param bool $expected
   *   The expected return value of ConfigNamesMapper::hasTranslation().
   *
   * @dataProvider providerTestHasTranslation
   */
  public function testHasTranslation(array $mock_return_values, $expected) {
    $language = new Language();

    // As the configuration names are arbitrary, simply use integers.
    $config_names = range(1, count($mock_return_values));
    $this->configNamesMapper->setConfigNames($config_names);

    $map = array();
    foreach ($config_names as $i => $config_name) {
      $map[] = array($config_name, $language, $mock_return_values[$i]);
    }
    $this->localeConfigManager
      ->expects($this->any())
      ->method('hasTranslation')
      ->will($this->returnValueMap($map));

    $result = $this->configNamesMapper->hasTranslation($language);
    $this->assertSame($expected, $result);
  }

  /**
   * Provides data for for ConfigNamesMapperTest::testHasTranslation().
   *
   * @return array
   *   An array of arrays, where each inner array has an array of values that
   *   the mocked configuration mapper manager should return for
   *   hasTranslation() as the first value and the expected return value of
   *   ConfigNamesMapper::hasTranslation() as the second value.
   */
  public function providerTestHasTranslation() {
    return array(
      array(array(TRUE), TRUE),
      array(array(FALSE), FALSE),
      array(array(TRUE, TRUE, TRUE), TRUE),
      array(array(FALSE, FALSE, TRUE), TRUE),
      array(array(FALSE, FALSE, FALSE), FALSE),
    );
  }

  /**
   * Tests ConfigNamesMapper::getTypeName().
   */
  public function testGetTypeName() {
    $result = $this->configNamesMapper->getTypeName();
    $this->assertSame('Settings', $result);
  }

  /**
   * Tests ConfigNamesMapper::hasTranslation().
   */
  public function testGetOperations() {
    $expected = array(
      'translate' => array(
        'title' => 'Translate',
        'href' => '/admin/config/system/site-information/translate',
      ),
    );
    $result = $this->configNamesMapper->getOperations();
    $this->assertEquals($expected, $result);
  }

}

/**
 * Defines a test mapper class.
 */
class TestConfigNamesMapper extends ConfigNamesMapper {

  /**
   * Gets the internal language code of this mapper, if any.
   *
   * This method is not to be confused with
   * ConfigMapperInterface::getLangcode().
   *
   * @return string|null
   *   The language code of this mapper if it is set; NULL otherwise.
   */
  public function getInternalLangcode() {
    return isset($this->langcode) ? $this->langcode : NULL;
  }

  /**
   * Sets the list of configuration names.
   *
   * @param array $config_names
   */
  public function setConfigNames(array $config_names) {
    $this->pluginDefinition['names'] = $config_names;
  }

  /**
   * Sets the configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory to set.
   */
  public function setConfigFactory(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

}
