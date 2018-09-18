<?php

namespace Drupal\ssofact;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the page_cache_request_policy service.
 */
class SsofactServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('page_cache_request_policy');
    $definition->setClass('Drupal\ssofact\PageCache\DefaultRequestPolicy');
  }

}
