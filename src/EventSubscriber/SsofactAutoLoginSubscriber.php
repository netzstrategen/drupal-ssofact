<?php

namespace Drupal\ssofact\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\openid_connect\Plugin\OpenIDConnectClientManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Automatically sends users with SSO cookie to SSO server for authentication.
 */
class SsofactAutoLoginSubscriber implements EventSubscriberInterface {

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
   * The curren user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new SsofactAutoLoginSubscriber object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    OpenIDConnectClientManager $plugin_manager,
    AccountProxyInterface $current_user
  ) {
    $this->configFactory = $config_factory;
    $this->pluginManager = $plugin_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run after:
    // - 300: AuthenticationSubscriber (to get current_user)
    // - 30: MaintenanceModeSubscriber (only local accounts may login during
    //   maintenance)
    // Run before:
    // - 27: DynamicPageCacheSubscriber
    $events[KernelEvents::REQUEST][] = ['onKernelRequestAuthenticate', 29];
    return $events;
  }

  /**
   * Redirects requests of unauthenticated users having a RF_OAUTH_SERVER cookie.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   */
  public function onKernelRequestAuthenticate(GetResponseEvent $event) {
    if ($this->currentUser->isAnonymous() && $event->getRequest()->cookies->has('RF_OAUTH_SERVER')) {
      // Do not interfere during any OpenID Connect authentication procedures.
      if (FALSE !== strpos($event->getRequest()->get('_route'), 'openid_connect') || FALSE !== strpos($event->getRequest()->get('_route'), 'ssofact')) {
        return;
      }
      $client_config = $this->configFactory->get('openid_connect.settings.ssofact')->get('settings');
      $client = $this->pluginManager->createInstance('ssofact', $client_config);

      $response = $client->authorize();
      $target_url = $response->getTargetUrl();
      $target_url .= '&' . http_build_query([
        'destination' => $event->getRequest()->getRequestUri(),
      ]);

      $response->setTrustedTargetUrl($target_url);
      $response->setStatusCode(307);
      $event->setResponse($response);
    }
  }

}
