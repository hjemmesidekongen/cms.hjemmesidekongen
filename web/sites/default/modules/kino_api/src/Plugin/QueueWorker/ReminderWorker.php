<?php

namespace Drupal\kino_api\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\SuspendQueueException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Content Hierarchy path updater.
 *
 * @QueueWorker(
 *   id = "kino_api_reminders",
 *   title = @Translation("Movie reminder worker"),
 *   cron = {"time" = 10}
 * )
 */
class ReminderWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected MailManagerInterface $mail;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, MailManagerInterface $mail) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->mail = $mail;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.mail')
    );
  }

  public function processItem($data) {
    $params['movie'] = $this->entityTypeManager->getStorage('node')->load($data['nid']);
    $message = $this->mail->mail('kino_api', 'reminder', $data['email'], \Drupal::languageManager()->getDefaultLanguage()->getId(), $params);
    if (!$message['result']) {
      throw new SuspendQueueException("Can't send reminder mail.");
    }
  }

}
