<?php

namespace Drupal\ssofact\PageCache;

use Drupal\ssofact\PageCache\RequestPolicy\SessionCookieRequestPolicy;
use Drupal\Core\Session\SessionConfigurationInterface;
use Drupal\Core\PageCache\DefaultRequestPolicy as PageCacheRequestPolicy;

/**
 * Overrides the default page cache policy service.
 *
 * Add a custom rule for ssoFACT session cookies.
 * Disallows serving responses from page cache for requests with a ssoFACT
 * session cookie.
 */
class DefaultRequestPolicy extends PageCacheRequestPolicy {

  /**
   * Constructs the default page cache request policy.
   */
  public function __construct(SessionConfigurationInterface $session_configuration) {
    parent::__construct($session_configuration);
    $this->addPolicy(new SessionCookieRequestPolicy());
  }

}
