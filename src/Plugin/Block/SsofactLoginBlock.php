<?php

namespace Drupal\ssofact\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Block\BlockBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a user login block.
 *
 * @Block(
 *   id = "ssofact_login_block",
 *   admin_label = @Translation("User login (ssoFACT)"),
 *   category = @Translation("Forms")
 * )
 */
class SsofactLoginBlock extends BlockBase implements ContainerFactoryPluginInterface {

  use RedirectDestinationTrait;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new UserLoginBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    if ($account->isAnonymous()) {
      return AccessResult::allowed()
        ->addCacheContexts(['route.name', 'user.roles:anonymous']);
    }
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\ssofact\Form\SsofactLoginForm');
    $form['login']['#size'] = 15;
    $form['pass']['#size'] = 15;

    // // Instead of setting an actual action URL, we set the placeholder, which
    // // will be replaced at the very last moment. This ensures forms with
    // // dynamically generated action URLs don't have poor cacheability.
    // // Use the proper API to generate the placeholder, when we have one. See
    // // https://www.drupal.org/node/2562341. The placholder uses a fixed string
    // // that is
    // // Crypt::hashBase64('\Drupal\user\Plugin\Block\UserLoginBlock::build');
    // // This is based on the implementation in
    // // \Drupal\Core\Form\FormBuilder::prepareForm(), but the user login block
    // // requires different behavior for the destination query argument.
    // $placeholder = 'form_action_p_4r8ITd22yaUvXM6SzwrSe9rnQWe48hz9k1Sxto3pBvE';
    //
    // $form['#attached']['placeholders'][$placeholder] = [
    //   '#lazy_builder' => ['\Drupal\user\Plugin\Block\UserLoginBlock::renderPlaceholderFormAction', []],
    // ];
    // $form['#action'] = $placeholder;

    $build = [
      'user_login_form' => $form,
      '#server_domain' => $form['#server_domain'],
    ];

    $build['social'] = [
      '#weight' => 20,
    ];
    $build['social']['heading'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['nfy-social-login-text']],
      '#markup' => 'Alternativ mit Facebook anmelden',
    ];
    $build['social']['facebook'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['fb-login-button'],
        'data-scope' => 'public_profile,email',
        'data-width' => '500',
        'onlogin' => 'nfyFacebookStatusCallback()',
        'data-max-rows' => '1',
        'data-size' => 'large',
        'data-button-type' => 'login_with',
        'data-show-faces' => 'false',
        'data-auto-logout-link' => 'false',
        'data-use-continue-as' => 'false',
      ],
    ];

    $build['register'] = [
      '#weight' => 30,
      '#type' => 'container',
      '#attributes' => ['class' => ['nfy-box-info', 'nfy-register-link-info']],
    ];
    $build['register']['box'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['nfy-box']],
    ];
    $build['register']['box']['question'] = [
      '#markup' => 'Sie sind noch nicht registriert?',
    ];
    $build['register']['box']['link'] = [
      '#type' => 'link',
      '#title' => $this->t('Register here'),
      '#url' => Url::fromUri('https://' . $form['#server_domain'] . '/registrieren.html'),
      '#attributes' => [
        'title' => $this->t('Create a new user account.'),
        'class' => ['nfy-link', 'create-account-link'],
      ],
    ];

    return $build;
  }

  /**
   * #lazy_builder callback; renders a form action URL including destination.
   *
   * @return array
   *   A renderable array representing the form action.
   *
   * @see \Drupal\Core\Form\FormBuilder::renderPlaceholderFormAction()
   */
  public static function renderPlaceholderFormAction() {
    return [
      '#type' => 'markup',
      '#markup' => Url::fromRoute('<current>', [], ['query' => \Drupal::destination()->getAsArray(), 'external' => FALSE])->toString(),
      '#cache' => ['contexts' => ['url.path', 'url.query_args']],
    ];
  }

}
