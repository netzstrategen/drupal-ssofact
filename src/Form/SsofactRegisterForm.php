<?php

namespace Drupal\ssofact\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\openid_connect\OpenIDConnectClaims;
use Drupal\openid_connect\Plugin\OpenIDConnectClientManager;
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
   * The constructor.
   *
   * @param \Drupal\openid_connect\Plugin\OpenIDConnectClientManager $plugin_manager
   *   The plugin manager.
   * @param \Drupal\openid_connect\OpenIDConnectClaims $claims
   *   The OpenID Connect claims.
   */
  public function __construct(
      OpenIDConnectClientManager $plugin_manager,
      OpenIDConnectClaims $claims
  ) {

    $this->pluginManager = $plugin_manager;
    $this->claims = $claims;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.openid_connect_client.processor'),
      $container->get('openid_connect.claims')
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
    // Use custom redirect_uri for client-side login form, which does not check
    // state token.
    $redirect_uri = Url::fromRoute('ssofact.redirect_login', [
      'client_name' => 'ssofact',
    ], [
      'absolute' => TRUE,
      'language' => \Drupal::languageManager()->getLanguage(LanguageInterface::LANGCODE_NOT_APPLICABLE),
      'query' => [
        'target' => Url::fromUri('internal:/')->toString(),
      ],
    ])->toString();

    $client_config = $client_config->get('settings');
    $client = $this->pluginManager->createInstance('ssofact', $client_config);
    $endpoints = $client->getEndpoints();
    $authorize_uri = $endpoints['user_create'] . '?' . http_build_query([
      'client_id' => $client_config['client_id'],
      'response_type' => 'code',
      'scope' => '',
      'redirect_uri' => $redirect_uri,
    ]);

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
    $form['redirect_url'] = [
      '#type' => 'hidden',
      // @todo Use current path.
      '#value' => Url::fromUri('internal:/')->toString(),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = ['#type' => 'submit', '#value' => $this->t('Sign up')];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $client_config = $this->config('openid_connect.settings.ssofact')->get('settings');
    $client = $this->pluginManager->createInstance('ssofact', $client_config);
    $response = $client->createUser($form_state->getValue('email'));

    if ($response['statuscode'] !== 200) {
      foreach ($response['userMessages'] as $error_message) {
        $form_state->setError($form['email'], $error_message);
      }
    }
    if (empty($response['userId'])) {
      $form_state->setError($form['email'], $this->t('Unable to register your account. Please contact our support.'));
    }
    else {
      $form_state->setTemporaryValue('userId', $response['userId']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($sub = $form_state->getTemporaryValue('userId', $response['userId']);) {
      // @todo Redirect to SSO login?
    }
    // openid_connect_save_destination();

    // $scopes = $this->claims->getScopes();
    // $_SESSION['openid_connect_op'] = 'login';
    // $response = $client->authorize($scopes, $form_state);
    // $form_state->setResponse($response);
  }

}
