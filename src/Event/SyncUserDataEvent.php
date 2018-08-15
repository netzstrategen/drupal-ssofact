<?php

namespace Drupal\ssofact\Event;

use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\Event;

class SyncUserDataEvent extends Event {

  const SYNC = 'event.sync_user_data';

  protected $userInfo;

  protected $user;


  public function __construct(UserInterface $user, array $userInfo) {
    $this->user = $user;
    $this->userInfo = $userInfo;
  }

  public function getUser() {
    return $this->user;
  }

  public function getUserInfo() {
    return $this->userInfo;
  }
}
