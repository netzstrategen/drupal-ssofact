<?php

namespace Drupal\ssofact\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\openid_connect\OpenIDConnectClaims;
use Drupal\openid_connect\Plugin\OpenIDConnectClientManager;
use Drupal\ssofact\Plugin\OpenIDConnectClient\Ssofact;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RegisterForm.
 *
 * @package Drupal\ssofact\Form
 */
class SsofactRegisterForm extends FormBase implements ContainerInjectionInterface {

  /**
   * Drupal\openid_connect\Plugin\OpenIDConnectClientManager definition.
   *
   * @var \Drupal\openid_connect\Plugin\OpenIDConnectClientManager
   */
  protected $pluginManager;

  /**
   * The OpenID Connect claims.
   *
   * @var \Drupal\openid_connect\OpenIDConnectClaims
   */
  protected $claims;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatch
   */
  protected $routeMatch;

  /**
   * The constructor.
   *
   * @param \Drupal\openid_connect\Plugin\OpenIDConnectClientManager $plugin_manager
   *   The plugin manager.
   * @param \Drupal\openid_connect\OpenIDConnectClaims $claims
   *   The OpenID Connect claims.
   */
  public function __construct(
      OpenIDConnectClientManager $plugin_manager,
      OpenIDConnectClaims $claims,
      CurrentRouteMatch $route_match
  ) {

    $this->pluginManager = $plugin_manager;
    $this->claims = $claims;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.openid_connect_client.processor'),
      $container->get('openid_connect.claims'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ssofact_register_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $client_config = $this->config('openid_connect.settings.ssofact');
    if (!$client_config->get('enabled')) {
      return $form;
    }

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Your email address'),
      '#size' => 60,
      '#maxlength' => USERNAME_MAX_LENGTH,
      '#required' => TRUE,
      '#attributes' => [
        'autocorrect' => 'none',
        'autocapitalize' => 'none',
        'spellcheck' => 'false',
        'autofocus' => 'autofocus',
      ],
    ];

    $form['article_test'] = [
      '#type' => 'hidden',
      '#value' => $this->routeMatch->getRawParameter('node'),
    ];

    $form['privacy'] = [
      '#type' => 'checkbox',
      '#value' => $this->t('I accept terms and conditions'),
      '#return_value' => '1',
    ];

    // Hidden field with value "1" to trigger special registration form behavior for 1-article-test.
    $form['_qf__registerForm'] = [
      '#type' => '1',
      '#value' => $this->routeMatch->getRawParameter('node'),
    ];

    $form['#action'] = 'https://' . $client_config['server_domain'] . '/registrieren.html?' . http_build_query([
      'next' => Url::fromUri('internal:/')->toString(),
    ]);

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = ['#type' => 'submit', '#value' => $this->t('Sign up')];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  public function validateEmail($form, FormStateInterface $form_state) {

  }

  private function isEmailRegistred($email) {
    $client_config = $this->config('openid_connect.settings.ssofact')->get('settings');
    $server_domain = $clinet_config['server_domain'];
    $api_url = 'https://' . SSOFACT_SERVER_DOMAIN . SsoFact::ENDPOINT_IS_EMAIL_REGISTERED;
    $client = \Drupal::httpClient();
    $client->post($api_url, [])
      'body' => [
        'email' => $email
      ],
      'headers' => [
        'Accept' => 'application/json',
        'rfbe-key' => SSOFACT_RFBE_KEY,
        'rfbe-secret' => SSOFACT_RFBE_SECRET,
      ],
    ]);
    if ($response instanceof \WP_Error) {
      static::displaySsoResponseError();
      return;
    }

  }

}
