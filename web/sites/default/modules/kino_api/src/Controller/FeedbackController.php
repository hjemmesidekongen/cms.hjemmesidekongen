<?php

namespace Drupal\kino_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\feedback\Entity\Feedback;
use Drupal\node\Entity\Node;
use Drupal\transform_api\Transform\EntityTransform;
use Drupal\transform_api\Transformer;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class FeedbackController extends ControllerBase {

  private Transformer $transformer;

  protected const page_size = 10;

  public function __construct(AccountInterface $currentUser, Transformer $transformer, EntityTypeManagerInterface $entityTypeManager) {
    $this->currentUser = $currentUser;
    $this->transformer = $transformer;
    $this->entityTypeManager = $entityTypeManager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('transform_api.transformer'),
      $container->get('entity_type.manager')
    );
  }

  public function addFeedback(Request $request): JsonResponse {
    $values = json_decode($request->getContent(), TRUE);

    if (!in_array($values['type'] ?? '', ['anticipation', 'comment', 'review'])) {
      return new JsonResponse(['error' => 'Invalid type'], 400);
    }
    if (empty($values['entity_type'])) {
      return new JsonResponse(['error' => 'Missing entity type.'], 400);
    }
    if (empty($values['entity_id'])) {
      return new JsonResponse(['error' => 'Missing entity id.'], 400);
    }
    if ($values['type'] != 'comment' && empty($values['field_rating'])) {
      return new JsonResponse(['error' => 'Missing rating field.'], 400);
    }
    if ($values['type'] === 'comment' && empty($values['field_body'])) {
      return new JsonResponse(['error' => 'Missing body field.'], 400);
    }

    $bundle = $values['type'];
    $entity_type_id = $values['entity_type'];
    $entity_id = $values['entity_id'];
    $rating = intval($values['field_rating'] ?? 0);
    $body = $values['field_body'] ?? '';
    $ip = $this->getIpAddress($request);

    $feedback = Feedback::create(['bundle' => $bundle]);
    $target = $this->entityTypeManager()->getStorage($entity_type_id)->load($entity_id);
    if ($this->currentUser()->isAuthenticated()) {
      $user = User::load($this->currentUser()->id());
      if (in_array($bundle, ['anticipation', 'review'])) {
        $exists = Feedback::loadByTargetUserAndType($target, $user, $bundle);
        if (is_object($exists)) {
          return new JsonResponse(['error' => 'User already has a ' . $bundle . '.'], 400);
        }
      }
    } else {
      $user = NULL;
    }
    $feedback->setTarget($target);
    $feedback->setIpAddress($ip);
    if ($feedback->hasField('field_rating')) {
      $feedback->set('field_rating', $rating);
    }
    if (!empty($body) && $feedback->hasField('field_body')) {
      $feedback->set('field_body', $body);
    }
    if (is_object($user)) {
      $feedback->setOwner($user);
    }
    $feedback->save();

    return new JsonResponse(['feedback_id' => $feedback->id()]);
  }

  public function editFeedback(Request $request, Feedback $feedback): JsonResponse {
    $values = json_decode($request->getContent(), TRUE);

    if ($feedback->bundle() == 'anticipation' && empty($values['field_rating'])) {
      return new JsonResponse(['error' => 'Missing rating field.'], 400);
    }
    if ($feedback->bundle() == 'comment' && empty($values['field_body'])) {
      return new JsonResponse(['error' => 'Missing body field.'], 400);
    }
    if ($this->currentUser->isAuthenticated() && $this->currentUser()->id() != $feedback->getOwnerId()) {
      return new JsonResponse(['error' => 'Not allowed to edit other users feedback.'], 403);
    } elseif ($this->currentUser->isAnonymous() && $this->getIpAddress($request) != $feedback->getIpAddress() && $feedback->getOwnerId() == 0) {
      return new JsonResponse(['error' => 'Not allowed to edit other users feedback.'], 403);
    } elseif ($this->currentUser->isAnonymous() && $feedback->getOwnerId() != 0) {
      return new JsonResponse(['error' => 'Not allowed to edit other users feedback.'], 403);
    }

    $rating = intval($values['field_rating'] ?? 0);
    $body = $values['field_body'] ?? '';

    $feedback->set('field_rating', $rating);
    if (!empty($body)) {
      $feedback->set('field_body', $body);
    }
    $feedback->save();

    return new JsonResponse(['feedback_id' => $feedback->id()]);
  }

  public function getFeedback(Node $node, $type, $page): JsonResponse {
    $storage = $this->entityTypeManager()->getStorage('feedback');
    $query = $storage->getQuery()
      ->condition('target_entity', $node->id())
      ->condition('entity_type', $node->getEntityTypeId())
      ->accessCheck();
    if ($type == 'review') {
      $query->condition('bundle', 'review')
        ->condition('status', 1)
        ->condition('field_full_review', 1);
    } else {
      $query->condition('bundle', 'comment');
    }
    $countQuery = clone $query;
    $items = $countQuery->count()->execute();
    $query->range($page * self::page_size, self::page_size);
    $query->sort('created', 'DESC');
    $ids = $query->execute();
    $entities = $storage->loadMultiple($ids);

    $result = [
      'feedback' => $this->transformer->transformRoot(EntityTransform::createFromMultipleEntities($entities)),
      'pager' => [
        'current' => intval($page),
        'limit' => self::page_size,
        'items' => $items,
        'pages' => ceil($items / self::page_size)
      ]
    ];

    return new JsonResponse($result);
  }

  public function deleteFeedback(Feedback $feedback): JsonResponse {
    if (!$feedback->access('delete')) {
      return new JsonResponse(['error' => 'User is not allowed to delete feedback.'], 403);
    }

    if ($feedback->bundle() == 'comment') {
      $feedback->setUnpublished();
      $feedback->set('field_body', '');
      $feedback->setOwnerId(0);
      $feedback->save();
    } else {
      $feedback->delete();
    }

    return new JsonResponse(['feedback_id' => $feedback->id()]);
  }

  protected function getIpAddress(Request $request): string {
    $ip = $request->get('ip', '');
    if (empty($ip) && $request->headers->has('x-client-ip')) {
      $ip = $request->headers->get('x-client-ip');
    } elseif (empty($ip) && $request->headers->has('x-forwarded-for')) {
      $ip = $request->headers->get('x-forwarded-for');
    }
    return $ip;
  }

}
