<?php

/**
 * @file
 * Contains \Drupal\Core\Page\HtmlFragment.
 */

namespace Drupal\Core\Page;

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Cache\CacheableInterface;
use Drupal\Core\Utility\Title;

/**
 * Basic mutable implementation of an HTML fragment.
 */
class HtmlFragment implements CacheableInterface, HtmlFragmentInterface {

  /**
   * An array of Link elements.
   *
   * @var array
   */
  protected $links = array();

  /**
   * An array of Meta elements.
   *
   * @var array
   */
  protected $metatags = array();

  /**
   * HTML content string.
   *
   * @var string
   */
  protected $content;

  /**
   * The title of this HtmlFragment.
   *
   * @var string
   */
  protected $title = '';

  /**
   * The cache metadata of this HtmlFragment.
   *
   * @var array
   */
  protected $cache = array();

  /**
   * Constructs a new HtmlFragment.
   *
   * @param string $content
   *   The content for this fragment.
   * @param array $cache_info
   *   The cache information.
   */
  public function __construct($content = '', array $cache_info = array()) {
    $this->content = $content;
    $this->cache = $cache_info + array(
      'keys' => array(),
      'tags' => array(),
      'max_age' => 0,
      'is_cacheable' => TRUE,
    );
  }

  /**
   * Adds a link element to the page.
   *
   * @param \Drupal\Core\Page\LinkElement $link
   *   A link element to enqueue.
   *
   * @return $this
   */
  public function addLinkElement(LinkElement $link) {
    $this->links[] = $link;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function &getLinkElements() {
    return $this->links;
  }

  /**
   * {@inheritdoc}
   */
  public function getFeedLinkElements() {
    $feed_links = array();
    foreach ($this->links as $link) {
      if ($link instanceof FeedLinkElement) {
        $feed_links[] = $link;
      }
    }
    return $feed_links;
  }

  /**
   * Adds a meta element to the page.
   *
   * @param \Drupal\Core\Page\MetaElement $meta
   *   A meta element to add.
   *
   * @return $this
   */
  public function addMetaElement(MetaElement $meta) {
    $this->metatags[] = $meta;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function &getMetaElements() {
    return $this->metatags;
  }

  /**
   * Sets the response content.
   *
   * This should be the bulk of the page content, and will ultimately be placed
   * within the <body> tag in final HTML output.
   *
   * Valid types are strings, numbers, and objects that implement a __toString()
   * method.
   *
   * @param mixed $content
   *   The content for this fragment.
   *
   * @return $this
   */
  public function setContent($content) {
    $this->content = $content;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getContent() {
    return $this->content;
  }

  /**
   * Sets the title of this HtmlFragment.
   *
   * Handling of this title varies depending on what is consuming this
   * HtmlFragment object. If it's a block, it may only be used as the block's
   * title; if it's at the page level, it will be used in a number of places,
   * including the html <head> title.
   *
   * @param string $title
   *   Value to assign to the page title.
   * @param int $output
   *   (optional) normally should be left as Title::CHECK_PLAIN. Only set to
   *   Title::PASS_THROUGH if you have already removed any possibly dangerous
   *   code from $title using a function like
   *   \Drupal\Component\Utility\String::checkPlain() or
   *   \Drupal\Component\Utility\Xss::filterAdmin(). With this flag the string
   *   will be passed through unchanged.
   *
   * @return $this
   */
  public function setTitle($title, $output = Title::CHECK_PLAIN) {
    if ($output == Title::CHECK_PLAIN) {
      $this->title = String::checkPlain($title);
    }
    else if ($output == Title::FILTER_XSS_ADMIN) {
      $this->title = Xss::filterAdmin($title);
    }
    else {
      $this->title = $title;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasTitle() {
    return !empty($this->title);
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * {@inheritdoc}
   *
   * @TODO Use a trait once we require php 5.4 for all the cache methods.
   */
  public function getCacheKeys() {
    return $this->cache['keys'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return $this->cache['tags'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return $this->cache['max_age'];
  }

  /**
   * {@inheritdoc}
   */
  public function isCacheable() {
    return $this->cache['is_cacheable'];
  }

}
