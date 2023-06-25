<?php

namespace Drupal\kino_api\Exception;

class UnauthorizedHttpException extends ApiException {

  public function __construct(?string $message = '', ?string $resultCode = '', array $additionalFields = [], \Throwable $previous = NULL, array $headers = [], ?int $code = 0) {
    parent::__construct(401, $message, $resultCode, $previous, $headers, $code, $additionalFields);
  }

}
