<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\HtmlViewSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Page\HtmlFragment;
use Drupal\Core\Page\HtmlPage;
use Drupal\Core\Page\HtmlFragmentRendererInterface;
use Drupal\Core\Page\HtmlPageRendererInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

/**
 * Main subscriber for Html-page responses.
 */
class HtmlViewSubscriber implements EventSubscriberInterface {

  /**
   * The fragment rendering service.
   *
   * @var \Drupal\Core\Page\HtmlFragmentRendererInterface
   */
  protected $fragmentRenderer;

  /**
   * The page rendering service.
   *
   * @var \Drupal\Core\Page\HtmlPageRendererInterface
   */
  protected $pageRenderer;

  /**
   * Constructs a new HtmlViewSubscriber.
   *
   * @param \Drupal\Core\Page\HtmlFragmentRendererInterface $fragment_renderer
   *   The fragment rendering service.
   * @param \Drupal\Core\Page\HtmlPageRendererInterface $page_renderer
   *   The page rendering service.
   */
  public function __construct(HtmlFragmentRendererInterface $fragment_renderer, HtmlPageRendererInterface $page_renderer) {
    $this->fragmentRenderer = $fragment_renderer;
    $this->pageRenderer = $page_renderer;
  }

  /**
   * Converts an HtmlFragment into an HtmlPage.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent $event
   *   The Event to process.
   */
  public function onHtmlFragment(GetResponseForControllerResultEvent $event) {
    $fragment = $event->getControllerResult();
    if ($fragment instanceof HtmlFragment && !$fragment instanceof HtmlPage) {
      $page = $this->fragmentRenderer->render($fragment);
      $event->setControllerResult($page);
    }
  }

  /**
   * Renders an HtmlPage object to a Response.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent $event
   *   The Event to process.
   */
  public function onHtmlPage(GetResponseForControllerResultEvent $event) {
    $page = $event->getControllerResult();
    if ($page instanceof HtmlPage) {
      // In case renderPage() returns NULL due to an error cast it to a string
      // so as to not cause issues with Response. This also allows renderPage
      // to return an object implementing __toString(), but that is not
      // recommended.
      $response = new Response((string) $this->pageRenderer->render($page), $page->getStatusCode());
      if ($tags = $page->getCacheTags()) {
        $response->headers->set('X-Drupal-Cache-Tags', implode(' ', $tags));
      }
      if ($keys = $page->getCacheKeys()) {
        $response->headers->set('cache_keys', serialize($keys));
      }
      if ($max_age = $page->getCacheMaxAge()) {
        $response->headers->set('cache_max_age', $max_age);
      }

      // Set the generator in the HTTP header.
      list($version) = explode('.', \Drupal::VERSION, 2);
      $response->headers->set('X-Generator', 'Drupal ' . $version . ' (http://drupal.org)');

      $event->setResponse($response);
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::VIEW][] = array('onHtmlFragment', 100);
    $events[KernelEvents::VIEW][] = array('onHtmlPage', 50);

    return $events;
  }

}
