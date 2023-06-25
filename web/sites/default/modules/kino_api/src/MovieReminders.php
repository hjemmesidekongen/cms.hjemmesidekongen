<?php

namespace Drupal\kino_api;

use Drupal\Core\Database\Connection;
use Drupal\node\Entity\Node;
use PDO;
use Symfony\Component\HttpFoundation\JsonResponse;

class MovieReminders {

  protected Connection $database;

  protected const delay = 3*60*60;

  public function __construct(Connection $database) {
    $this->database = $database;
  }

  public function addReminder(Node $node, $email): bool {
    $id = $this->database->select('reminder_queue', 'rq')
      ->fields('rq', ['id'])
      ->condition('nid', $node->id())
      ->condition('email', $email)
      ->execute()
      ->fetchField();
    if (empty($id)) {
      $this->database->insert('reminder_queue')
        ->fields([
          'nid' => $node->id(),
          'email' => $email,
          'timestamp' => $this->getTimestamp($node)
        ])
        ->execute();
      return TRUE;
    } else {
      return FALSE;
    }
  }

  public function getReminders(Node $node): array {
    return $this->database->select('reminder_queue', 'rq')
      ->fields('rq', ['email'])
      ->condition('nid', $node->id())
      ->execute()
      ->fetchCol();
  }

  public function updateReminders(Node $node): bool {
    if ($this->getTimestamp($node) > 0) {
      $this->database->update('reminder_queue')
        ->condition('nid', $node->id())
        ->fields(['timestamp' => $this->getTimestamp($node)])
        ->execute();
      return TRUE;
    } else {
      return $this->deleteReminders($node);
    }
  }

  public function deleteReminder($id): bool {
    $this->database->delete('reminder_queue')
      ->condition('id', $id)
      ->execute();
    return TRUE;
  }

  public function deleteReminders(Node $node): bool {
    $this->database->delete('reminder_queue')
      ->condition('nid', $node->id())
      ->execute();
    return TRUE;
  }

  public function getDueReminders(): array {
    $reminders = $this->database->select('reminder_queue', 'rq')
      ->fields('rq', ['id', 'nid', 'email'])
      ->condition('timestamp', time(), '<')
      ->orderBy('id')
      ->range(0, 100)
      ->execute()
      ->fetchAll(PDO::FETCH_ASSOC);
    if ($reminders === FALSE) {
      return [];
    }

    $ids = [];
    foreach ($reminders as $reminder) {
      $ids[] = $reminder['id'];
    }
    if (!empty($ids)) {
      $this->database->delete('reminder_queue')
        ->condition('id', $ids, 'IN')
        ->execute();
    }

    return $reminders;
  }

  protected function getTimestamp(Node $node) {
    if ($node->get('field_premiere')->isEmpty() || is_null($node->get('field_premiere')->first()) || empty($node->get('field_premiere')->first()->getValue()['value'] ?? '')) {
      return 0;
    }
    return strtotime($node->get('field_premiere')->first()->getValue()['value'] ?? '') - self::delay;
  }
}
