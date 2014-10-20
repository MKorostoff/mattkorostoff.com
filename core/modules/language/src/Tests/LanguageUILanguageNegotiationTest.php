<?php

/**
 * @file
 * Definition of Drupal\language\Tests\LanguageUILanguageNegotiationTest.
 */

namespace Drupal\language\Tests;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationBrowser;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationSelected;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\user\Plugin\LanguageNegotiation\LanguageNegotiationUser;
use Drupal\user\Plugin\LanguageNegotiation\LanguageNegotiationUserAdmin;
use Drupal\simpletest\WebTestBase;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\language\LanguageNegotiatorInterface;

/**
 * Tests UI language switching.
 *
 * 1. URL (PATH) > DEFAULT
 *    UI Language base on URL prefix, browser language preference has no
 *    influence:
 *      admin/config
 *        UI in site default language
 *      zh-hans/admin/config
 *        UI in Chinese
 *      blah-blah/admin/config
 *        404
 * 2. URL (PATH) > BROWSER > DEFAULT
 *        admin/config
 *          UI in user's browser language preference if the site has that
 *          language added, if not, the default language
 *        zh-hans/admin/config
 *          UI in Chinese
 *        blah-blah/admin/config
 *          404
 * 3. URL (DOMAIN) > DEFAULT
 *        http://example.com/admin/config
 *          UI language in site default
 *        http://example.cn/admin/config
 *          UI language in Chinese
 *
 * @group language
 */
class LanguageUILanguageNegotiationTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * We marginally use interface translation functionality here, so need to use
   * the locale module instead of language only, but the 90% of the test is
   * about the negotiation process which is solely in language module.
   *
   * @var array
   */
  public static $modules = array('locale', 'language_test', 'block', 'user');

  protected function setUp() {
    parent::setUp();

    $admin_user = $this->drupalCreateUser(array('administer languages', 'translate interface', 'access administration pages', 'administer blocks'));
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests for language switching by URL path.
   */
  function testUILanguageNegotiation() {
    // A few languages to switch to.
    // This one is unknown, should get the default lang version.
    $langcode_unknown = 'blah-blah';
    // For testing browser lang preference.
    $langcode_browser_fallback = 'vi';
    // For testing path prefix.
    $langcode = 'zh-hans';
    // For setting browser language preference to 'vi'.
    $http_header_browser_fallback = array("Accept-Language: $langcode_browser_fallback;q=1");
    // For setting browser language preference to some unknown.
    $http_header_blah = array("Accept-Language: blah;q=1");

    // Setup the site languages by installing two languages.
    // Set the default language in order for the translated string to be registered
    // into database when seen by t(). Without doing this, our target string
    // is for some reason not found when doing translate search. This might
    // be some bug.
    $default_language = \Drupal::languageManager()->getDefaultLanguage();
    ConfigurableLanguage::createFromLangcode($langcode_browser_fallback)->save();
    \Drupal::config('system.site')->set('langcode', $langcode_browser_fallback)->save();
    ConfigurableLanguage::createFromLangcode($langcode)->save();

    // We will look for this string in the admin/config screen to see if the
    // corresponding translated string is shown.
    $default_string = 'Hide descriptions';

    // First visit this page to make sure our target string is searchable.
    $this->drupalGet('admin/config');

    // Now the t()'ed string is in db so switch the language back to default.
    // This will rebuild the container so we need to rebuild the container in
    // the test environment.
    \Drupal::config('system.site')->set('langcode', $default_language->getId())->save();
    \Drupal::config('language.negotiation')->set('url.prefixes.en', '')->save();
    $this->rebuildContainer();

    // Translate the string.
    $language_browser_fallback_string = "In $langcode_browser_fallback In $langcode_browser_fallback In $langcode_browser_fallback";
    $language_string = "In $langcode In $langcode In $langcode";
    // Do a translate search of our target string.
    $search = array(
      'string' => $default_string,
      'langcode' => $langcode_browser_fallback,
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $textarea = current($this->xpath('//textarea'));
    $lid = (string) $textarea[0]['name'];
    $edit = array(
      $lid => $language_browser_fallback_string,
    );
    $this->drupalPostForm('admin/config/regional/translate', $edit, t('Save translations'));

    $search = array(
      'string' => $default_string,
      'langcode' => $langcode,
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $textarea = current($this->xpath('//textarea'));
    $lid = (string) $textarea[0]['name'];
    $edit = array(
      $lid => $language_string,
    );
    $this->drupalPostForm('admin/config/regional/translate', $edit, t('Save translations'));

    // Configure selected language negotiation to use zh-hans.
    $edit = array('selected_langcode' => $langcode);
    $this->drupalPostForm('admin/config/regional/language/detection/selected', $edit, t('Save configuration'));
    $test = array(
      'language_negotiation' => array(LanguageNegotiationSelected::METHOD_ID),
      'path' => 'admin/config',
      'expect' => $language_string,
      'expected_method_id' => LanguageNegotiationSelected::METHOD_ID,
      'http_header' => $http_header_browser_fallback,
      'message' => 'SELECTED: UI language is switched based on selected language.',
    );
    $this->runTest($test);

    // An invalid language is selected.
    \Drupal::config('language.negotiation')->set('selected_langcode', NULL)->save();
    $test = array(
      'language_negotiation' => array(LanguageNegotiationSelected::METHOD_ID),
      'path' => 'admin/config',
      'expect' => $default_string,
      'expected_method_id' => LanguageNegotiatorInterface::METHOD_ID,
      'http_header' => $http_header_browser_fallback,
      'message' => 'SELECTED > DEFAULT: UI language is switched based on selected language.',
    );
    $this->runTest($test);

    // No selected language is available.
    \Drupal::config('language.negotiation')->set('selected_langcode', $langcode_unknown)->save();
    $test = array(
      'language_negotiation' => array(LanguageNegotiationSelected::METHOD_ID),
      'path' => 'admin/config',
      'expect' => $default_string,
      'expected_method_id' => LanguageNegotiatorInterface::METHOD_ID,
      'http_header' => $http_header_browser_fallback,
      'message' => 'SELECTED > DEFAULT: UI language is switched based on selected language.',
    );
    $this->runTest($test);

    $tests = array(
      // Default, browser preference should have no influence.
      array(
        'language_negotiation' => array(LanguageNegotiationUrl::METHOD_ID, LanguageNegotiationSelected::METHOD_ID),
        'path' => 'admin/config',
        'expect' => $default_string,
        'expected_method_id' => LanguageNegotiatorInterface::METHOD_ID,
        'http_header' => $http_header_browser_fallback,
        'message' => 'URL (PATH) > DEFAULT: no language prefix, UI language is default and the browser language preference setting is not used.',
      ),
      // Language prefix.
      array(
        'language_negotiation' => array(LanguageNegotiationUrl::METHOD_ID, LanguageNegotiationSelected::METHOD_ID),
        'path' => "$langcode/admin/config",
        'expect' => $language_string,
        'expected_method_id' => LanguageNegotiationUrl::METHOD_ID,
        'http_header' => $http_header_browser_fallback,
        'message' => 'URL (PATH) > DEFAULT: with language prefix, UI language is switched based on path prefix',
      ),
      // Default, go by browser preference.
      array(
        'language_negotiation' => array(LanguageNegotiationUrl::METHOD_ID, LanguageNegotiationBrowser::METHOD_ID),
        'path' => 'admin/config',
        'expect' => $language_browser_fallback_string,
        'expected_method_id' => LanguageNegotiationBrowser::METHOD_ID,
        'http_header' => $http_header_browser_fallback,
        'message' => 'URL (PATH) > BROWSER: no language prefix, UI language is determined by browser language preference',
      ),
      // Prefix, switch to the language.
      array(
        'language_negotiation' => array(LanguageNegotiationUrl::METHOD_ID, LanguageNegotiationBrowser::METHOD_ID),
        'path' => "$langcode/admin/config",
        'expect' => $language_string,
        'expected_method_id' => LanguageNegotiationUrl::METHOD_ID,
        'http_header' => $http_header_browser_fallback,
        'message' => 'URL (PATH) > BROWSER: with language prefix, UI language is based on path prefix',
      ),
      // Default, browser language preference is not one of site's lang.
      array(
        'language_negotiation' => array(LanguageNegotiationUrl::METHOD_ID, LanguageNegotiationBrowser::METHOD_ID, LanguageNegotiationSelected::METHOD_ID),
        'path' => 'admin/config',
        'expect' => $default_string,
        'expected_method_id' => LanguageNegotiatorInterface::METHOD_ID,
        'http_header' => $http_header_blah,
        'message' => 'URL (PATH) > BROWSER > DEFAULT: no language prefix and browser language preference set to unknown language should use default language',
      ),
    );

    foreach ($tests as $test) {
      $this->runTest($test);
    }

    // Unknown language prefix should return 404.
    $definitions = \Drupal::languageManager()->getNegotiator()->getNegotiationMethods();
    \Drupal::config('language.types')
      ->set('negotiation.' . LanguageInterface::TYPE_INTERFACE . '.enabled', array_flip(array_keys($definitions)))
      ->save();
    $this->drupalGet("$langcode_unknown/admin/config", array(), $http_header_browser_fallback);
    $this->assertResponse(404, "Unknown language path prefix should return 404");

    // Set preferred langcode for user to NULL.
    $account = $this->loggedInUser;
    $account->preferred_langcode = NULL;
    $account->save();

    $test = array(
      'language_negotiation' => array(LanguageNegotiationUser::METHOD_ID, LanguageNegotiationSelected::METHOD_ID),
      'path' => 'admin/config',
      'expect' => $default_string,
      'expected_method_id' => LanguageNegotiatorInterface::METHOD_ID,
      'http_header' => array(),
      'message' => 'USER > DEFAULT: no preferred user language setting, the UI language is default',
    );
    $this->runTest($test);

    // Set preferred langcode for user to unknown language.
    $account = $this->loggedInUser;
    $account->preferred_langcode = $langcode_unknown;
    $account->save();

    $test = array(
      'language_negotiation' => array(LanguageNegotiationUser::METHOD_ID, LanguageNegotiationSelected::METHOD_ID),
      'path' => 'admin/config',
      'expect' => $default_string,
      'expected_method_id' => LanguageNegotiatorInterface::METHOD_ID,
      'http_header' => array(),
      'message' => 'USER > DEFAULT: invalid preferred user language setting, the UI language is default',
    );
    $this->runTest($test);

    // Set preferred langcode for user to non default.
    $account->preferred_langcode = $langcode;
    $account->save();

    $test = array(
      'language_negotiation' => array(LanguageNegotiationUser::METHOD_ID, LanguageNegotiationSelected::METHOD_ID),
      'path' => 'admin/config',
      'expect' => $language_string,
      'expected_method_id' => LanguageNegotiationUser::METHOD_ID,
      'http_header' => array(),
      'message' => 'USER > DEFAULT: defined prefereed user language setting, the UI language is based on user setting',
    );
    $this->runTest($test);

    // Set preferred admin langcode for user to NULL.
    $account->preferred_admin_langcode = NULL;
    $account->save();

    $test = array(
      'language_negotiation' => array(LanguageNegotiationUserAdmin::METHOD_ID, LanguageNegotiationSelected::METHOD_ID),
      'path' => 'admin/config',
      'expect' => $default_string,
      'expected_method_id' => LanguageNegotiatorInterface::METHOD_ID,
      'http_header' => array(),
      'message' => 'USER ADMIN > DEFAULT: no preferred user admin language setting, the UI language is default',
    );
    $this->runTest($test);

    // Set preferred admin langcode for user to unknown language.
    $account->preferred_admin_langcode = $langcode_unknown;
    $account->save();

    $test = array(
      'language_negotiation' => array(LanguageNegotiationUserAdmin::METHOD_ID, LanguageNegotiationSelected::METHOD_ID),
      'path' => 'admin/config',
      'expect' => $default_string,
      'expected_method_id' => LanguageNegotiatorInterface::METHOD_ID,
      'http_header' => array(),
      'message' => 'USER ADMIN > DEFAULT: invalid preferred user admin language setting, the UI language is default',
    );
    $this->runTest($test);

    // Set preferred admin langcode for user to non default.
    $account->preferred_admin_langcode = $langcode;
    $account->save();

    $test = array(
      'language_negotiation' => array(LanguageNegotiationUserAdmin::METHOD_ID, LanguageNegotiationSelected::METHOD_ID),
      'path' => 'admin/config',
      'expect' => $language_string,
      'expected_method_id' => LanguageNegotiationUserAdmin::METHOD_ID,
      'http_header' => array(),
      'message' => 'USER ADMIN > DEFAULT: defined prefereed user admin language setting, the UI language is based on user setting',
    );
    $this->runTest($test);
  }

  protected function runTest($test) {
    if (!empty($test['language_negotiation'])) {
      $method_weights = array_flip($test['language_negotiation']);
      $this->container->get('language_negotiator')->saveConfiguration(LanguageInterface::TYPE_INTERFACE, $method_weights);
    }
    if (!empty($test['language_negotiation_url_part'])) {
      \Drupal::config('language.negotiation')
        ->set('url.source', $test['language_negotiation_url_part'])
        ->save();
    }
    if (!empty($test['language_test_domain'])) {
      \Drupal::state()->set('language_test.domain', $test['language_test_domain']);
    }
    $this->container->get('language_manager')->reset();
    $this->drupalGet($test['path'], array(), $test['http_header']);
    $this->assertText($test['expect'], $test['message']);
    $this->assertText(t('Language negotiation method: @name', array('@name' => $test['expected_method_id'])));
  }

  /**
   * Test URL language detection when the requested URL has no language.
   */
  function testUrlLanguageFallback() {
    // Add the Italian language.
    $langcode_browser_fallback = 'it';
    ConfigurableLanguage::createFromLangcode($langcode_browser_fallback)->save();
    $languages = $this->container->get('language_manager')->getLanguages();

    // Enable the path prefix for the default language: this way any unprefixed
    // URL must have a valid fallback value.
    $edit = array('prefix[en]' => 'en');
    $this->drupalPostForm('admin/config/regional/language/detection/url', $edit, t('Save configuration'));

    // Enable browser and URL language detection.
    $edit = array(
      'language_interface[enabled][language-browser]' => TRUE,
      'language_interface[enabled][language-url]' => TRUE,
      'language_interface[weight][language-browser]' => -8,
      'language_interface[weight][language-url]' => -10,
    );
    $this->drupalPostForm('admin/config/regional/language/detection', $edit, t('Save settings'));
    $this->drupalGet('admin/config/regional/language/detection');

    // Enable the language switcher block.
    $this->drupalPlaceBlock('language_block:' . LanguageInterface::TYPE_INTERFACE, array('id' => 'test_language_block'));

    // Log out, because for anonymous users, the "active" class is set by PHP
    // (which means we can easily test it here), whereas for authenticated users
    // it is set by JavaScript.
    $this->drupalLogout();

    // Access the front page without specifying any valid URL language prefix
    // and having as browser language preference a non-default language.
    $http_header = array("Accept-Language: $langcode_browser_fallback;q=1");
    $language = new Language(array('id' => ''));
    $this->drupalGet('', array('language' => $language), $http_header);

    // Check that the language switcher active link matches the given browser
    // language.
    $args = array(':id' => 'block-test-language-block', ':url' => base_path() . $GLOBALS['script_path'] . $langcode_browser_fallback);
    $fields = $this->xpath('//div[@id=:id]//a[@class="language-link active" and starts-with(@href, :url)]', $args);
    $this->assertTrue($fields[0] == $languages[$langcode_browser_fallback]->name, 'The browser language is the URL active language');

    // Check that URLs are rewritten using the given browser language.
    $fields = $this->xpath('//strong[@class="site-name"]/a[@rel="home" and @href=:url]', $args);
    $this->assertTrue($fields[0] == 'Drupal', 'URLs are rewritten using the browser language.');
  }

  /**
   * Tests _url() when separate domains are used for multiple languages.
   */
  function testLanguageDomain() {
    // Add the Italian language.
    ConfigurableLanguage::createFromLangcode('it')->save();

    $languages = $this->container->get('language_manager')->getLanguages();

    // Enable browser and URL language detection.
    $edit = array(
      'language_interface[enabled][language-url]' => TRUE,
      'language_interface[weight][language-url]' => -10,
    );
    $this->drupalPostForm('admin/config/regional/language/detection', $edit, t('Save settings'));

    // Change the domain for the Italian language.
    $edit = array(
      'language_negotiation_url_part' => LanguageNegotiationUrl::CONFIG_DOMAIN,
      'domain[it]' => 'it.example.com',
    );
    $this->drupalPostForm('admin/config/regional/language/detection/url', $edit, t('Save configuration'));
    $this->rebuildContainer();

    // Build the link we're going to test.
    $link = 'it.example.com' . rtrim(base_path(), '/') . '/admin';

    // Test URL in another language: http://it.example.com/admin.
    // Base path gives problems on the testbot, so $correct_link is hard-coded.
    // @see UrlAlterFunctionalTest::assertUrlOutboundAlter (path.test).
    $italian_url = _url('admin', array('language' => $languages['it'], 'script' => ''));
    $url_scheme = \Drupal::request()->isSecure() ? 'https://' : 'http://';
    $correct_link = $url_scheme . $link;
    $this->assertEqual($italian_url, $correct_link, format_string('The _url() function returns the right URL (@url) in accordance with the chosen language', array('@url' => $italian_url)));

    // Test HTTPS via options.
    $this->settingsSet('mixed_mode_sessions', TRUE);
    $this->rebuildContainer();

    $italian_url = _url('admin', array('https' => TRUE, 'language' => $languages['it'], 'script' => ''));
    $correct_link = 'https://' . $link;
    $this->assertTrue($italian_url == $correct_link, format_string('The _url() function returns the right HTTPS URL (via options) (@url) in accordance with the chosen language', array('@url' => $italian_url)));
    $this->settingsSet('mixed_mode_sessions', FALSE);

    // Test HTTPS via current URL scheme.
    $request = Request::create('', 'GET', array(), array(), array(), array('HTTPS' => 'on'));
    $this->container->get('request_stack')->push($request);
    $generator = $this->container->get('url_generator');
    $italian_url = _url('admin', array('language' => $languages['it'], 'script' => ''));
    $correct_link = 'https://' . $link;
    $this->assertTrue($italian_url == $correct_link, format_string('The _url() function returns the right URL (via current URL scheme) (@url) in accordance with the chosen language', array('@url' => $italian_url)));
  }
}
