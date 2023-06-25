<?php

namespace Drupal\kino_api\Authentication\Provider;

use Drupal\basic_auth\Authentication\Provider\BasicAuth;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * HTTP Basic authentication provider.
 */
class EmailAuth extends BasicAuth {

  /**
   * Get the entered email and convert it to the username in order to
   * authenticate with BasicAuth.
   */
  public function authenticate(Request $request): EntityInterface|AccountInterface|null {
    $mail = $request->headers->get('PHP_AUTH_USER');

    /** @var \Drupal\user\Entity\User|null $user */
    $user = user_load_by_mail($mail);
    $username = $user ? $user->getAccountName() : '';
    $request->headers->set('PHP_AUTH_USER', $username);

    return parent::authenticate($request);
  }

}
