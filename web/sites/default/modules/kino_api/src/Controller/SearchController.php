<?php

namespace Drupal\kino_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\relewise\Relewise;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class SearchController extends ControllerBase {

  private Relewise $relewise;

  public function __construct(Relewise $relewise) {
    $this->relewise = $relewise;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('relewise')
    );
  }

  public function searchBar(Request $request): JsonResponse {
    $text = $request->query->get('text', '');
    if (empty($text)) {
      return new JsonResponse([]);
    }
    $this->relewise->searchContent($text, [], 10, 0);
  }

}
