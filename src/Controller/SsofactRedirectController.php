<?php

namespace Drupal\ssofact\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles inbound client authorization code requests.
 */
class SsofactRedirectController extends ControllerBase implements AccessInterface {

  /**
   * The request stack used to access request globals.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    RequestStack $request_stack
  ) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

  /**
   * Access callback: Redirect page.
   *
   * @return bool
   *   Whether the state token matches the previously created one that is stored
   *   in the session.
   */
  public function access() {
    $query = $this->requestStack->getCurrentRequest()->query;
    if ($query->get('code') && $query->get('target')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  /**
   * Redirects the user to the ssoFACT password reset form template.
   */
  public function passwordReset() {
    $client_config = $this->config('openid_connect.settings.ssofact');
    if (!$client_config->get('enabled')) {
      return;
    }
    $client_config = $client_config->get('settings');
    $url = Url::fromUri('https://' . $client_config['server_domain'] . '/index.php?' . http_build_query([
      'pageid' => 53,
      'next' => Url::fromUri('internal:/shop/user/account', ['absolute' => TRUE])->toString(),
    ]));
    $response = new RedirectResponse($url->toString());
    $response->send();
    return;
  }

}
