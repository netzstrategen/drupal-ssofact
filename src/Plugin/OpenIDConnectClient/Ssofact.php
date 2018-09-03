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
  const ENDPOINT_END_SESSION = '/REST/oauth/logout';

  const ENDPOINT_USER_CREATE = '/REST/services/authenticate/user/registerUser';
  const ENDPOINT_IS_EMAIL_REGISTERED = '/REST/services/authenticate/user/IsEmailRegistered';

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
      'rfbe_key' => '',
      'rfbe_secret' => '',
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
    $form['rfbe_key'] = [
      '#title' => $this->t('RFBE key'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['rfbe_key'],
    ];
    $form['rfbe_secret'] = [
      '#title' => $this->t('RFBE secret'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['rfbe_secret'],
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
      'end_session' => 'https://' . $this->configuration['server_domain'] . static::ENDPOINT_END_SESSION,
      'user_create' => 'https://' . $this->configuration['server_domain'] . static::ENDPOINT_USER_CREATE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function authorize($scope = 'openid email') {
    // Non-empty scope triggers error:
    // "Authorization failed: invalid_scope. Details: Unsupported Scope".
    return parent::authorize($this->configuration['scope']);
  }

  /**
   * {@inheritdoc}
   */
  public function decodeIdToken($id_token) {
    /*
    $this->loggerFactory->get('ssofact')->debug('id_token: @id_token', [
      '@id_token' => "<pre>\n" . var_export($id_token, TRUE) . "</pre>",
    ]);
    */
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveUserInfo($access_token) {
    $userinfo = parent::retrieveUserInfo($access_token);

    if ($userinfo) {
      // Documentation states unique_user_hash, but vendor instructed to use ID.
      $userinfo['sub'] = $userinfo['id'];
      $userinfo['preferred_username'] = $userinfo['email'];
      $userinfo['email_verified'] = $userinfo['confirmed'];
      $userinfo['updated_at'] = $userinfo['lastchgdate'];

      $this->loggerFactory->get('ssofact')->debug('Userinfo: @userinfo', [
        '@userinfo' => "<pre>\n" . var_export($userinfo, TRUE) . "</pre>",
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

  public function createUser($email) {
    $redirect_uri = Url::fromUri('internal:/shop/user/confirm')->toString();
    $endpoints = $this->getEndpoints();
    $request_options = [
      'form_params' => [
        'email' => $email,
        /*
        'optins' => [
          'confirm_agb' => 0,
          'acquisitionMail' => 0,
          'acquisitionEmail' => 0,
          'acquisitionPhone' => 0,
          'list_noch-fragen' => 0,
          'list_premium' => 0,
          'list_freizeit' => 0,
        ],
        */
        'confirmationUrl' => $redirect_uri,
      ],
      'headers' => [
        'Accept' => 'application/json',
        'rfbe-key' => $this->configuration['rfbe_key'],
        'rfbe-secret' => $this->configuration['rfbe_secret'],
      ],
    ];
    try {
      $response = $this->httpClient->post($endpoints['user_create'], $request_options);
      $response_data = json_decode((string) $response->getBody(), TRUE);

      $this->loggerFactory->get('ssofact')->debug('User register response: @response', [
        '@response' => "<pre>\n" . var_export($response_data, TRUE) . "</pre>",
      ]);
      return $response_data;
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('ssofact' . $this->pluginId)
        ->error('User register failed: @error_message', [
          '@error_message' => $e->getMessage(),
        ]);
      throw $e;
    }
  }

  public function isEmailRegistered($email) {
    $rfbe_key = $this->configuration['rfbe_key'];
    $rfbe_secret = $this->configuration['rfbe_secret'];
    $api_url = 'https://' . $this->configuration['server_domain'] . static::ENDPOINT_IS_EMAIL_REGISTERED;
    $client = \Drupal::httpClient();
    $request = $client->post($api_url, [
      'body' => json_encode(['email' => $email]),
      'headers' => [
        'Content-type' => 'application/json',
        'Accept' => 'application/json',
        'rfbe-key' => $rfbe_key,
        'rfbe-secret' => $rfbe_secret,
      ],
    ]);
    $response = json_decode($request->getBody());
    return [
      'status' => (int) $response->statuscode,
      'message' => $response->userMessages[0],
    ];
  }

}
