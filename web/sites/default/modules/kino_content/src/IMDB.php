<?php

namespace Drupal\kino_content;

class IMDB {

  public static function IdToUrl($id) {
    return 'https://www.imdb.com/title/' . $id . '/';
  }

  public static function UrlToId($url) {
    $path = trim(parse_url($url, PHP_URL_PATH));
    $paths = explode('/', substr($path, 1, -1));
    return $paths[array_key_last($paths)];
  }

}
