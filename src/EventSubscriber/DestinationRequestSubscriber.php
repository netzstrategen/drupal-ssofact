<?php

namespace Drupal\ssofact\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Translates the ssoFACT query parameter 'target' into Drupal's 'destination'.
 */
class DestinationRequestSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run as early as possible.
    $events[KernelEvents::REQUEST][] = ['onKernelRequest', 500];
    return $events;
  }

  /**
   * Translates the ssoFACT query parameter 'target' into Drupal's 'destination'.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   */
  public function onKernelRequest(GetResponseEvent $event) {
    if ($event->getRequest()->query->has('target')) {
      $event->getRequest()->query->set('destination', $event->getRequest()->query->get('target'));
    }
  }

}
