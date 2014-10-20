<?php

/**
 * @file
 * Contains Drupal\system\Tests\Menu\LocalTasksTest.
 */

namespace Drupal\system\Tests\Menu;

use Drupal\simpletest\WebTestBase;

/**
 * Tests local tasks derived from router and added/altered via hooks.
 *
 * @group Menu
 */
class LocalTasksTest extends WebTestBase {

  public static $modules = array('menu_test', 'entity_test');

  /**
   * Asserts local tasks in the page output.
   *
   * @param array $hrefs
   *   A list of expected link hrefs of local tasks to assert on the page (in
   *   the given order).
   * @param int $level
   *   (optional) The local tasks level to assert; 0 for primary, 1 for
   *   secondary. Defaults to 0.
   */
  protected function assertLocalTasks(array $hrefs, $level = 0) {
    $elements = $this->xpath('//*[contains(@class, :class)]//a', array(
      ':class' => $level == 0 ? 'tabs primary' : 'tabs secondary',
    ));
    $this->assertTrue(count($elements), 'Local tasks found.');
    foreach ($hrefs as $index => $element) {
      $expected = _url($hrefs[$index]);
      $method = ($elements[$index]['href'] == $expected ? 'pass' : 'fail');
      $this->{$method}(format_string('Task @number href @value equals @expected.', array(
        '@number' => $index + 1,
        '@value' => (string) $elements[$index]['href'],
        '@expected' => $expected,
      )));
    }
  }

  /**
   * Tests the plugin based local tasks.
   */
  public function testPluginLocalTask() {
    // Verify that local tasks appear as defined in the router.
    $this->drupalGet('menu-local-task-test/tasks');

    $this->drupalGet('menu-local-task-test/tasks/view');
    $this->assertLocalTasks(array(
      'menu-local-task-test/tasks/view',
      'menu-local-task-test/tasks/settings',
      'menu-local-task-test/tasks/edit',
    ));

    // Ensure the view tab is active.
    $result = $this->xpath('//ul[contains(@class, "tabs")]//li[contains(@class, "active")]/a');
    $this->assertEqual(1, count($result), 'There is just a single active tab.');
    $this->assertEqual('View', (string) $result[0], 'The view tab is active.');

    // Verify that local tasks in the second level appear.
    $sub_tasks = array(
      'menu-local-task-test/tasks/settings/sub1',
      'menu-local-task-test/tasks/settings/sub2',
      'menu-local-task-test/tasks/settings/sub3',
      'menu-local-task-test/tasks/settings/derive1',
      'menu-local-task-test/tasks/settings/derive2',
    );
    $this->drupalGet('menu-local-task-test/tasks/settings');
    $this->assertLocalTasks($sub_tasks, 1);

    $result = $this->xpath('//ul[contains(@class, "tabs")]//li[contains(@class, "active")]/a');
    $this->assertEqual(1, count($result), 'There is just a single active tab.');
    $this->assertEqual('Settings', (string) $result[0], 'The settings tab is active.');

    $this->drupalGet('menu-local-task-test/tasks/settings/sub1');
    $this->assertLocalTasks($sub_tasks, 1);

    $result = $this->xpath('//ul[contains(@class, "tabs")]//a[contains(@class, "active")]');
    $this->assertEqual(2, count($result), 'There are tabs active on both levels.');
    $this->assertEqual('Settings', (string) $result[0], 'The settings tab is active.');
    $this->assertEqual('Dynamic title for TestTasksSettingsSub1', (string) $result[1], 'The sub1 tab is active.');

    $this->drupalGet('menu-local-task-test/tasks/settings/derive1');
    $this->assertLocalTasks($sub_tasks, 1);

    $result = $this->xpath('//ul[contains(@class, "tabs")]//li[contains(@class, "active")]');
    $this->assertEqual(2, count($result), 'There are tabs active on both levels.');
    $this->assertEqual('Settings', (string) $result[0]->a, 'The settings tab is active.');
    $this->assertEqual('Derive 1', (string) $result[1]->a, 'The derive1 tab is active.');

    // Ensures that the local tasks contains the proper 'provider key'
    $definitions = $this->container->get('plugin.manager.menu.local_task')->getDefinitions();
    $this->assertEqual($definitions['menu_test.local_task_test_tasks_view']['provider'], 'menu_test');
    $this->assertEqual($definitions['menu_test.local_task_test_tasks_edit']['provider'], 'menu_test');
    $this->assertEqual($definitions['menu_test.local_task_test_tasks_settings']['provider'], 'menu_test');
    $this->assertEqual($definitions['menu_test.local_task_test_tasks_settings_sub1']['provider'], 'menu_test');
    $this->assertEqual($definitions['menu_test.local_task_test_tasks_settings_sub2']['provider'], 'menu_test');
    $this->assertEqual($definitions['menu_test.local_task_test_tasks_settings_sub3']['provider'], 'menu_test');

    // Test that we we correctly apply the active class to tabs where one of the
    // request attributes is upcast to an entity object.
    $entity = \Drupal::entityManager()->getStorage('entity_test')->create(array('bundle' => 'test'));
    $entity->save();

    $this->drupalGet('menu-local-task-test-upcasting/1/sub1');

    $tasks = array(
      'menu-local-task-test-upcasting/1/sub1',
      'menu-local-task-test-upcasting/1/sub2',
    );
    $this->assertLocalTasks($tasks, 0);

    $result = $this->xpath('//ul[contains(@class, "tabs")]//li[contains(@class, "active")]');
    $this->assertEqual(1, count($result), 'There is one active tab.');
    $this->assertEqual('upcasting sub1', (string) $result[0]->a, 'The "upcasting sub1" tab is active.');

    $this->drupalGet('menu-local-task-test-upcasting/1/sub2');

    $tasks = array(
      'menu-local-task-test-upcasting/1/sub1',
      'menu-local-task-test-upcasting/1/sub2',
    );
    $this->assertLocalTasks($tasks, 0);

    $result = $this->xpath('//ul[contains(@class, "tabs")]//li[contains(@class, "active")]');
    $this->assertEqual(1, count($result), 'There is one active tab.');
    $this->assertEqual('upcasting sub2', (string) $result[0]->a, 'The "upcasting sub2" tab is active.');
  }

}
