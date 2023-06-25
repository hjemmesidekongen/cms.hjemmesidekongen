<?php

namespace Drupal\kino_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\kino_api\MovieReminders;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ReminderController extends ControllerBase {

  private MovieReminders $reminders;

  protected const page_size = 10;

  public function __construct(AccountInterface $currentUser, EntityTypeManagerInterface $entityTypeManager, MovieReminders $reminders) {
    $this->currentUser = $currentUser;
    $this->entityTypeManager = $entityTypeManager;
    $this->reminders = $reminders;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('kino_api.reminders')
    );
  }

  public function addReminder(Request $request, Node $node): JsonResponse {
    $values = json_decode($request->getContent(), TRUE);
    $email = $values['email'];

    if ($node->get('field_premiere')->isEmpty()) {
      return new JsonResponse(['error' => 'Movie has no premiere date.'], 400);
    }
    $timestamp = strtotime($node->get('field_premiere')->first()->getValue()['value']);
    if ($timestamp < time()) {
      return new JsonResponse(['error' => "Movie already had it's premiere."], 400);
    }

    if ($this->reminders->addReminder($node, $email)) {
      return new JsonResponse(['message' => 'E-mail added to movie reminder list.']);
    } else {
      return new JsonResponse(['error' => 'E-mail already added to movie reminder list.'], 400);
    }
  }

  public function getReminders(Node $node): JsonResponse {
    return new JsonResponse($this->reminders->getReminders($node));
  }

}
