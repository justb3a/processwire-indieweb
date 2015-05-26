<?php

namespace Kfi\IndieWeb;

use WireException;

class Webmentions {

  public function __construct() {
    $this->start();
  }

  public function start() {
    $post = wire('input')->post;

    $source = wire('sanitizer')->url($post['source']);
    $target = wire('sanitizer')->url($post['target']);

    if (empty($source)) {
      header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
      throw new WireException(__('Invalid source'));
    }

    if (empty($target)) {
      header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
      throw new WireException(__('Invalid target'));
    }

    $http = wire('config')->https === true ? 'https' : 'http';
    $site = "$http://" . wire('config')->httpHost . '/';
    if (!strpos($target, $site)) {
      header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
      throw new WireException(__('Invalid target'));
    }
  }
}
