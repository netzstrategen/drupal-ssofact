<?php

namespace Drupal\ssofact\PageCache\RequestPolicy;

use Drupal\Core\PageCache\RequestPolicyInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Reject caching when the user has the sso cookie.
 */
class SessionCookieRequestPolicy implements RequestPolicyInterface {

  /**
   * {@inheritdoc}
   */
  public function check(Request $request) {
    if ($request->cookies->has('RF_OAUTH_SERVER')) {
      return static::DENY;
    }
  }

}
