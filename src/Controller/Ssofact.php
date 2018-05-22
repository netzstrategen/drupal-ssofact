<?php

namespace Drupal\ssofact\Controller;

use Drupal\Core\Controller\ControllerBase;

class Ssofact extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function content() {
    return [
      '#title' => '{{ title }}',
      '#markup' => '{{ content }}',
    ];
  }

}
