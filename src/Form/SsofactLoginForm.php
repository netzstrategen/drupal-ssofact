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
 * Class LoginForm.
 *
 * @package Drupal\ssofact\Form
 */
class SsofactLoginForm extends FormBase implements ContainerInjectionInterface {

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
    return 'ssofact_login_form';
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
        'target' => \Drupal::request()->query->get('destination') ?: Url::fromUri('internal:/')->toString(),
      ],
    ])->toString();

    $client_config = $client_config->get('settings');
    $client = $this->pluginManager->createInstance('ssofact', $client_config);
    $endpoints = $client->getEndpoints();
    $authorize_uri = $endpoints['authorization'] . '?' . http_build_query([
      'client_id' => $client_config['client_id'],
      'response_type' => 'code',
      'scope' => '',
      'redirect_uri' => $redirect_uri,
    ]);

    $form['#action'] = 'https://' . $client_config['server_domain'] . '/?' . http_build_query([
      'next' => $authorize_uri,
    ]);
    // Allow theme template to access the server domain easily.
    $form['#server_domain'] = $client_config['server_domain'];

    // @todo External URLs need a dynamic domain name.
    // @todo CSS is loaded before theme, causing styles to be reset.
    // $form['#attached']['library'][] = 'ssofact/form';

    $form['#attributes']['class'][] = 'nfy-form';
    $form['#attributes']['class'][] = 'nfy-flex-form';

    $form['login'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email address'),
      '#title_display' => 'invisible',
      '#placeholder' => 'E-Mail-Adresse eingeben',
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
    $form['pass'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#title_display' => 'invisible',
      '#placeholder' => 'Passwort eingeben',
      '#size' => 60,
      '#required' => TRUE,
    ];
    $form['permanent_login'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Stay logged-in'),
      '#value' => 1,
      '#wrapper_attributes' => [
        'class' => ['nfy-checkbox', 'checkbox'],
      ],
    ];
    $form['request_password'] = [
      '#type' => 'link',
      '#title' => $this->t('Forgot password?'),
      '#url' => Url::fromUri('https://' . $form['#server_domain'] . '/index.php?' . http_build_query([
        'pageid' => 53,
        'next' => Url::fromUri('internal:/shop/user/account', ['absolute' => TRUE])->toString(),
      ]), [
        'attributes' => [
          'title' => $this->t('Send password reset instructions via email.'),
          'class' => ['button--link', 'button', 'request-password-link'],
        ],
      ]),
    ];

    $form['redirect_url'] = [
      '#type' => 'hidden',
      '#value' => '',
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Log in'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    openid_connect_save_destination();
    $client_name = $form_state->getTriggeringElement()['#name'];

    $configuration = $this->config('openid_connect.settings.' . $client_name)
      ->get('settings');
    $client = $this->pluginManager->createInstance(
      $client_name,
      $configuration
    );
    $scopes = $this->claims->getScopes();
    $_SESSION['openid_connect_op'] = 'login';
    $response = $client->authorize($scopes, $form_state);
    $form_state->setResponse($response);
  }

}
