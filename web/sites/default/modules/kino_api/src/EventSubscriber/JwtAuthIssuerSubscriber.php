<?php

namespace Drupal\kino_api\EventSubscriber;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jwt\Authentication\Event\JwtAuthEvents;
use Drupal\jwt\Authentication\Event\JwtAuthGenerateEvent;
use Drupal\jwt\Authentication\Event\JwtAuthValidateEvent;
use Drupal\jwt_auth_consumer\EventSubscriber\JwtAuthConsumerSubscriber;
use Drupal\kino_content\Transform\DisplayNameTransform;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JwtAuthIssuerSubscriber extends JwtAuthConsumerSubscriber {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   */
  public function __construct(AccountInterface $user, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($entity_type_manager);
    $this->currentUser = $user;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[JwtAuthEvents::GENERATE][] = ['setKinoClaims', 98];
    $events[JwtAuthEvents::VALIDATE][] = ['validate'];
    return $events;
  }

  /**
   * Sets claims for a Drupal consumer on the JWT.
   *
   * @param \Drupal\jwt\Authentication\Event\JwtAuthGenerateEvent $event
   *   The event.
   */
  public function setKinoClaims(JwtAuthGenerateEvent $event) {
    $displayName = DisplayNameTransform::getDisplayName($this->currentUser->id());
    $event->addClaim(
      ['drupal', 'displayName'],
      $displayName
    );
    $event->addClaim(
      ['drupal', 'email'],
      $this->currentUser->getEmail()
    );
  }

  /**
   * Validates that a uid, uuid, or name is present in the JWT.
   *
   * This validates the format of the JWT and validate the uid, uuid, or name
   * corresponds to a valid user in the system.
   *
   * @param \Drupal\jwt\Authentication\Event\JwtAuthValidateEvent $event
   *   A JwtAuth event.
   */
  public function validate(JwtAuthValidateEvent $event) {
    $token = $event->getToken();
    /** @var \Drupal\user\Entity\User $user */
    [$user, $reason] = $this->loadUserForJwt($token);
    if ($user) {
      $displayName = DisplayNameTransform::getDisplayName($user->id());
      if ($displayName != $token->getClaim(['drupal', 'displayName'])) {
        $event->invalidate('Display name do not match');
      }
      if ($user->getEmail() != $token->getClaim(['drupal', 'email'])) {
        $event->invalidate('E-mail do not match');
      }
    }
  }

}
