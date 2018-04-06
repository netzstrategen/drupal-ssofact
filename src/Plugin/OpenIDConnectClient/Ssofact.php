<?php

namespace Drupal\ssofact\Plugin\OpenIDConnectClient;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\openid_connect\Plugin\OpenIDConnectClientBase;

/**
 * Newsfactory ssoFACT OpenID Connect client.
 *
 * @OpenIDConnectClient(
 *   id = "ssofact",
 *   label = "ssoFACT"
 * )
 */
class Ssofact extends OpenIDConnectClientBase {

  const ENDPOINT_AUTHORIZE = '/REST/oauth/authorize';
  const ENDPOINT_TOKEN = '/REST/oauth/access_token';
  const ENDPOINT_USERINFO = '/REST/oauth/user';

  const ROUTE_REDIRECT = 'ssofact.redirect';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'client_id' => '',
      'client_secret' => '',
      'server_domain' => '',
      'scope' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['server_domain'] = [
      '#title' => $this->t('Server domain'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['server_domain'],
      '#field_prefix' => 'https://',
      '#placeholder' => 'login.example.com',
      '#field_suffix' => static::ENDPOINT_AUTHORIZE,
    ];
    $form['scope'] = [
      '#title' => $this->t('Scope'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['scope'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndpoints() {
    return [
      'authorization' => 'https://' . $this->configuration['server_domain'] . static::ENDPOINT_AUTHORIZE,
      'token' => 'https://' . $this->configuration['server_domain'] . static::ENDPOINT_TOKEN,
      'userinfo' => 'https://' . $this->configuration['server_domain'] . static::ENDPOINT_USERINFO,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function authorize($scope = 'openid email') {
    // Non-empty scope triggers error:
    // "Authorization failed: invalid_scope. Details: Unsupported Scope"
    return parent::authorize($this->configuration['scope']);
  }

  /**
   * {@inheritdoc}
   */
  public function decodeIdToken($id_token) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveUserInfo($access_token) {
    $userinfo = parent::retrieveUserInfo($access_token);

    if ($userinfo) {
      $this->loggerFactory->get('ssofact')->debug('Userinfo: @userinfo', [
        '@userinfo' => var_export($userinfo, TRUE),
      ]);
    }
    return $userinfo;
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl(array $route_parameters = [], array $options = []) {
    return parent::getRedirectUrl([], ['https' => NULL] + $options);
  }

}
