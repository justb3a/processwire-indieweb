<?php

namespace Kfi\IndieWeb;

require_once(wire('config')->paths->IndieWeb . 'vendor/autoload.php');
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

    if (!strpos($target, wire('config')->httpHost)) {
      header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
      throw new WireException(__('Invalid target'));
    }

    $data = \Mf2\fetch($source);
    $result = '';
    if (count($data['items'])) {
      $result = \IndieWeb\comments\parse($data['items'][0], $source);
    }

    if (empty($result)) {
      throw new WireException(__('Probably spam'));
    }

    //@todo: use / save result
  }

}
