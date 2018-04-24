<?php

namespace Drupal\ssofact\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\openid_connect\Plugin\OpenIDConnectClientManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirects users to EndSession endpoint of SSO server upon logout.
 */
class UserLogoutResponseFilter implements EventSubscriberInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The OpenID Connect client plugin manager.
   *
   * @var \Drupal\openid_connect\Plugin\OpenIDConnectClientManager
   */
  protected $pluginManager;

  /**
   * Constructs a new ActiveLinkResponseFilter instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\openid_connect\Plugin\OpenIDConnectClientManager $plugin_manager
   *   The OpenID Connect client plugin manager service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    OpenIDConnectClientManager $plugin_manager
  ) {
    $this->configFactory = $config_factory;
    $this->pluginManager = $plugin_manager;
  }

  /**
   * Changes redirect response of user logout to EndSession endpoint of ssoFACT.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The response event.
   */
  public function onResponse(FilterResponseEvent $event) {
    $request = $event->getRequest();
    $response = $event->getResponse();
    if ($response instanceof RedirectResponse && $request->attributes->get('_route') === 'user.logout') {
      $client_config = $this->configFactory->get('openid_connect.settings.ssofact')->get('settings');
      $client = $this->pluginManager->createInstance('ssofact', $client_config);
      $endpoints = $client->getEndpoints();
      $query_string = http_build_query([
        'redirect_uri' => Url::fromUri('internal:/', ['absolute' => TRUE])->toString(),
      ]);
      $response = TrustedRedirectResponse::createFromRedirectResponse($response);
      $response->setStatusCode(307);
      $response->setTrustedTargetUrl($endpoints['end_session'] . '/' . $client_config['client_id'] . '?' . $query_string);
      $event->setResponse($response);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onResponse', 0];
    return $events;
  }

}
