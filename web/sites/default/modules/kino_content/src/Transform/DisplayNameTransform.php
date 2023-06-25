<?php

namespace Drupal\kino_content\Transform;

use Drupal\transform_api\Transform\TransformBase;
use Drupal\user\Entity\User;

class DisplayNameTransform extends TransformBase {

  public function __construct(int $uid) {
    $this->setUserId($uid);
  }

  public function setUserId($uid) {
    $this->values['uid'] = $uid;
    $this->setCacheTags(['user:' . $uid]);
  }

  public function getUserId() {
    return $this->values['uid'];
  }

  public function getUser(): User {
    return User::load($this->values['uid']);
  }

  public function getTransformType() {
    return 'display_name';
  }

  /**
   * @inheritDoc
   */
  public function transform() {
    $result = [
      '#collapse' => TRUE,
      'displayName' => self::getDisplayName($this->getUserId())
    ];
    $this->applyTo($result);
    return $result;
  }

  public static function getDisplayName($uid) {
    if (empty($uid)) {
      return t('Anonymous');
    }
    $user = User::load($uid);
    if (empty($user)) {
      return t('Anonymous');
    } else {
      if ($user->get('field_alias')->isEmpty() || $user->get('field_alias')
          ->first()
          ->getString() == '') {
        if ($user->get('field_name')->isEmpty() || $user->get('field_name')
            ->first()
            ->getString() == '') {
          $displayName = $user->label();
        }
        else {
          $displayName = $user->get('field_name')->first()->getString();
        }
      }
      else {
        $displayName = $user->get('field_alias')->first()->getString();
      }
    }
    return $displayName;
  }

}
