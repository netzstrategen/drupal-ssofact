<?php

namespace Drupal\ssofact\Event;

use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Implements event for user data sincroniztion.
 */
class SyncUserDataEvent extends Event {

  const SYNC = 'event.sync_user_data';

  protected $userInfo;

  protected $user;

  /**
   * {@inheritdoc}
   */
  public function __construct(UserInterface $user, array $userInfo) {
    $this->user = $user;
    $this->userInfo = $userInfo;
  }

  /**
   * Get the user being synced.
   */
  public function getUser() {
    return $this->user;
  }

  /**
   * Get user info to sync.
   */
  public function getUserInfo() {
    return $this->userInfo;
  }

}
