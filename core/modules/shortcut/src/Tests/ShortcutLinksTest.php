<?php

/**
 * @file
 * Contains \Drupal\shortcut\Tests\ShortcutLinksTest.
 */

namespace Drupal\shortcut\Tests;
use Drupal\shortcut\Entity\Shortcut;
use Drupal\shortcut\Entity\ShortcutSet;

/**
 * Create, view, edit, delete, and change shortcut links.
 *
 * @group shortcut
 */
class ShortcutLinksTest extends ShortcutTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('router_test', 'views');

  /**
   * Tests that creating a shortcut works properly.
   */
  public function testShortcutLinkAdd() {
    $set = $this->set;

    // Create an alias for the node so we can test aliases.
    $path = array(
      'source' => 'node/' . $this->node->id(),
      'alias' => $this->randomMachineName(8),
    );
    $this->container->get('path.alias_storage')->save($path['source'], $path['alias']);

    // Create some paths to test.
    $test_cases = array(
      array('path' => '', 'route_name' => '<front>'),
      array('path' => '<front>', 'route_name' => '<front>'),
      array('path' => 'admin', 'route_name' => 'system.admin'),
      array('path' => 'admin/config/system/site-information', 'route_name' => 'system.site_information_settings'),
      array('path' => 'node/' . $this->node->id() . '/edit', 'route_name' => 'entity.node.edit_form'),
      array('path' => $path['alias'], 'route_name' => 'entity.node.canonical'),
      array('path' => 'router_test/test2', 'route_name' => 'router_test.2'),
      array('path' => 'router_test/test3/value', 'route_name' => 'router_test.3'),
    );

    // Check that each new shortcut links where it should.
    foreach ($test_cases as $test) {
      $title = $this->randomMachineName();
      $form_data = array(
        'title[0][value]' => $title,
        'path' => $test['path'],
      );
      $this->drupalPostForm('admin/config/user-interface/shortcut/manage/' . $set->id() . '/add-link', $form_data, t('Save'));
      $this->assertResponse(200);
      $saved_set = ShortcutSet::load($set->id());
      $routes = $this->getShortcutInformation($saved_set, 'route_name');
      $this->assertTrue(in_array($test['route_name'], $routes), 'Shortcut created: ' . $test['path']);
      $this->assertLink($title, 0, 'Shortcut link found on the page.');
    }
    $saved_set = ShortcutSet::load($set->id());
    // Test that saving and re-loading a shortcut preserves its values.
    $shortcuts = $saved_set->getShortcuts();
    foreach ($shortcuts as $entity) {
      // Test the node routes with parameters.
      if (strpos($entity->route_name->value, 'node.') === 0) {
        $entity->save();
        $loaded = Shortcut::load($entity->id());
        $this->assertEqual($entity->route_name->value, $loaded->route_name->value);
        $this->assertEqual($entity->get('route_parameters')->first()->getValue(), $loaded->get('route_parameters')->first()->getValue());
      }
    }

    // Login as non admin user, to check that access is checked when creating
    // shortcuts.
    $this->drupalLogin($this->shortcut_user);
    $title = $this->randomMachineName();
    $form_data = [
      'title[0][value]' => $title,
      'path' => 'admin',
    ];
    $this->drupalPostForm('admin/config/user-interface/shortcut/manage/' . $set->id() . '/add-link', $form_data, t('Save'));
    $this->assertResponse(200);
    $this->assertRaw(t('The shortcut must correspond to a valid path on the site.'));

    $form_data = [
      'title[0][value]' => $title,
      'path' => 'node',
    ];
    $this->drupalPostForm('admin/config/user-interface/shortcut/manage/' . $set->id() . '/add-link', $form_data, t('Save'));
    $this->assertLink($title, 0, 'Shortcut link found on the page.');
  }

  /**
   * Tests that the "add to shortcut" and "remove from shortcut" links work.
   */
  public function testShortcutQuickLink() {
    \Drupal::service('theme_handler')->install(array('seven'));
    \Drupal::config('system.theme')->set('admin', 'seven')->save();
    $this->container->get('config.factory')->get('node.settings')->set('use_admin_theme', '1')->save();
    $this->container->get('router.builder')->rebuild();

    $this->drupalLogin($this->root_user);
    $this->drupalGet('admin/config/system/cron');

    // Test the "Add to shortcuts" link.
    $this->clickLink('Add to Default shortcuts');
    $this->assertText('Added a shortcut for Cron.');
    $this->assertLink('Cron', 0, 'Shortcut link found on page');

    $this->drupalGet('admin/structure');
    $this->assertLink('Cron', 0, 'Shortcut link found on different page');

    // Test the "Remove from shortcuts" link.
    $this->clickLink('Cron');
    $this->clickLink('Remove from Default shortcuts');
    $this->assertText('The shortcut Cron has been deleted.');
    $this->assertNoLink('Cron', 'Shortcut link removed from page');

    $this->drupalGet('admin/structure');
    $this->assertNoLink('Cron', 'Shortcut link removed from different page');
  }

  /**
   * Tests that shortcut links can be renamed.
   */
  public function testShortcutLinkRename() {
    $set = $this->set;

    // Attempt to rename shortcut link.
    $new_link_name = $this->randomMachineName();

    $shortcuts = $set->getShortcuts();
    $shortcut = reset($shortcuts);
    $this->drupalPostForm('admin/config/user-interface/shortcut/link/' . $shortcut->id(), array('title[0][value]' => $new_link_name, 'path' => $shortcut->path->value), t('Save'));
    $saved_set = ShortcutSet::load($set->id());
    $titles = $this->getShortcutInformation($saved_set, 'title');
    $this->assertTrue(in_array($new_link_name, $titles), 'Shortcut renamed: ' . $new_link_name);
    $this->assertLink($new_link_name, 0, 'Renamed shortcut link appears on the page.');
  }

  /**
   * Tests that changing the path of a shortcut link works.
   */
  public function testShortcutLinkChangePath() {
    $set = $this->set;

    // Tests changing a shortcut path.
    $new_link_path = 'admin/config';

    $shortcuts = $set->getShortcuts();
    $shortcut = reset($shortcuts);
    $this->drupalPostForm('admin/config/user-interface/shortcut/link/' . $shortcut->id(), array('title[0][value]' => $shortcut->getTitle(), 'path' => $new_link_path), t('Save'));
    $saved_set = ShortcutSet::load($set->id());
    $routes = $this->getShortcutInformation($saved_set, 'route_name');
    $this->assertTrue(in_array('system.admin_config', $routes), 'Shortcut path changed: ' . $new_link_path);
    $this->assertLinkByHref($new_link_path, 0, 'Shortcut with new path appears on the page.');
  }

  /**
   * Tests that changing the route of a shortcut link works.
   */
  public function testShortcutLinkChangeRoute() {
    $this->drupalLogin($this->root_user);
    $this->drupalGet('admin/content');
    $this->assertResponse(200);
    // Disable the view.
    entity_load('view', 'content')->disable()->save();
    $this->drupalGet('admin/content');
    $this->assertResponse(200);
  }

  /**
   * Tests deleting a shortcut link.
   */
  public function testShortcutLinkDelete() {
    $set = $this->set;

    $shortcuts = $set->getShortcuts();
    $shortcut = reset($shortcuts);
    $this->drupalPostForm('admin/config/user-interface/shortcut/link/' . $shortcut->id() . '/delete', array(), 'Delete');
    $saved_set = ShortcutSet::load($set->id());
    $ids = $this->getShortcutInformation($saved_set, 'id');
    $this->assertFalse(in_array($shortcut->id(), $ids), 'Successfully deleted a shortcut.');

    // Delete all the remaining shortcut links.
    entity_delete_multiple('shortcut', array_filter($ids));

    // Get the front page to check that no exceptions occur.
    $this->drupalGet('');
  }

  /**
   * Tests that the add shortcut link is not displayed for 404/403 errors.
   *
   * Tests that the "Add to shortcuts" link is not displayed on a page not
   * found or a page the user does not have access to.
   */
  public function testNoShortcutLink() {
    // Change to a theme that displays shortcuts.
    \Drupal::service('theme_handler')->install(array('seven'));
    \Drupal::config('system.theme')
      ->set('default', 'seven')
      ->save();

    $this->drupalGet('page-that-does-not-exist');
    $result = $this->xpath('//div[contains(@class, "add-shortcut")]');
    $this->assertTrue(empty($result), 'Add to shortcuts link was not shown on a page not found.');

    // The user does not have access to this path.
    $this->drupalGet('admin/modules');
    $result = $this->xpath('//div[contains(@class, "add-shortcut")]');
    $this->assertTrue(empty($result), 'Add to shortcuts link was not shown on a page the user does not have access to.');

    // Verify that the testing mechanism works by verifying the shortcut
    // link appears on admin/people.
    $this->drupalGet('admin/people');
    $result = $this->xpath('//div[contains(@class, "remove-shortcut")]');
    $this->assertTrue(!empty($result), 'Remove from shortcuts link was shown on a page the user does have access to.');

    // Verify that the shortcut link appears on routing only pages.
    $this->drupalGet('router_test/test2');
    $result = $this->xpath('//div[contains(@class, "add-shortcut")]');
    $this->assertTrue(!empty($result), 'Add to shortcuts link was shown on a page the user does have access to.');
  }

  /**
   * Tests that the 'access shortcuts' permissions works properly.
   */
  public function testAccessShortcutsPermission() {
    // Change to a theme that displays shortcuts.
    \Drupal::service('theme_handler')->install(array('seven'));
    \Drupal::config('system.theme')
      ->set('default', 'seven')
      ->save();

    // Add cron to the default shortcut set.
    $this->drupalLogin($this->root_user);
    $this->drupalGet('admin/config/system/cron');
    $this->clickLink('Add to Default shortcuts');

    // Verify that users without the 'access shortcuts' permission can't see the
    // shortcuts.
    $this->drupalLogin($this->drupalCreateUser(array('access toolbar')));
    $this->assertNoLink('Shortcuts', 0, 'Shortcut link not found on page.');

    // Verify that users with the 'access shortcuts' permission can see the
    // shortcuts.
    $this->drupalLogin($this->drupalCreateUser(array('access toolbar', 'access shortcuts')));
    $this->clickLink('Shortcuts', 0, 'Shortcut link found on page.');
    $this->assertLink('Cron', 0, 'Cron shortcut link found on page.');

    $this->verifyAccessShortcutsPermissionForEditPages();
  }

  /**
   * Tests that the 'access shortcuts' permission is required for shortcut set
   * administration page access.
   */
  private function verifyAccessShortcutsPermissionForEditPages() {
    // Create a user with customize links and switch sets permissions  but
    // without the 'access shortcuts' permission.
    $test_permissions = array(
      'customize shortcut links',
      'switch shortcut sets',
    );
    $noaccess_user = $this->drupalCreateUser($test_permissions);
    $this->drupalLogin($noaccess_user);

    // Verify that set administration pages are inaccessible without the
    // 'access shortcuts' permission.
    $edit_paths = array(
      'admin/config/user-interface/shortcut/manage/default/customize',
      'admin/config/user-interface/shortcut/manage/default',
      'user/' . $noaccess_user->id() . '/shortcuts',
    );

    foreach ($edit_paths as $path) {
      $this->drupalGet($path);
      $message = format_string('Access is denied on %s', array('%s' => $path));
      $this->assertResponse(403, $message);
    }
  }

}
