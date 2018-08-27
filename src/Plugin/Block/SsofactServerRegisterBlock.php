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
 *   id = "ssofact_server_register_block",
 *   admin_label = @Translation("User server register (ssoFACT)"),
 *   category = @Translation("Forms")
 * )
 */
class SsofactServerRegisterBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
    $form = \Drupal::formBuilder()->getForm('Drupal\ssofact\Form\SsofactServerRegisterForm');
    unset($form['email']['#attributes']['autofocus']);
    $form['email']['#size'] = 15;

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

    // Build action links.
    $items = [];
    if (\Drupal::config('user.settings')->get('register') != USER_REGISTER_ADMINISTRATORS_ONLY) {
      $items['login'] = [
        '#type' => 'link',
        '#title' => $this->t('Log in'),
        '#url' => Url::fromRoute('user.login', [], [
          'attributes' => [
            'title' => $this->t('Log in with your existing account.'),
            'class' => ['login-link'],
          ],
        ]),
      ];
    }
    $items['request_password'] = [
      '#type' => 'link',
      '#title' => $this->t('Reset your password'),
      '#url' => Url::fromRoute('user.pass', [], [
        'attributes' => [
          'title' => $this->t('Send password reset instructions via email.'),
          'class' => ['request-password-link'],
        ],
      ]),
    ];
    return [
      'user_server_register_form' => $form,
      'user_links' => [
        '#theme' => 'item_list',
        '#items' => $items,
      ],
    ];
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
