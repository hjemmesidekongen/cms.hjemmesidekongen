<?php

namespace Drupal\kino_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;
use Kino\ShowtimesToken;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ShowtimesController extends ControllerBase {

  private ShowtimesToken $token;

  protected function getTokenService() {
    if (empty($this->token)) {
      $this->token = new ShowtimesToken(file_get_contents('../jwt_keys/rsa.key'), file_get_contents('../jwt_keys/iv.key'));
    }
    return $this->token;
  }

  public function user($token) {
    $data = $this->getTokenService()->decodeToken($token);
    if (empty($data)) {
      return new JsonResponse(NULL, 400);
    }

    $uid = $data['uid'];
    $user = User::load($uid);
    $result = [
      'id' => $user->id(),
      'email' => $user->getEmail(),
      'name' => $user->get('field_name')->first()->getString(),
      'alias' => $user->get('field_alias')->first()->getString(),
      'address' => $user->get('field_address')->first()->getString(),
      'phone' => $user->get('field_phone_number')->first()->getString()
    ];
    return new JsonResponse($result);
  }

  public function generateToken(User $user, $cinema, $movie) {
    $content =  $this->getTokenService()->generateToken($user->id(), $cinema, $movie);
    return new Response($content);
  }

}
