<?php

namespace Kfi\IndieWeb;

require_once(wire('config')->paths->IndieWeb . 'vendor/autoload.php');
require_once(wire('config')->paths->IndieWeb . 'lib/Mention.php');
use WireException;

class Webmentions {

  public $source = null;
  public $target = null; // own site link
  public $result = '';

  /**
   * construct
   */
  public function __construct() {
    $post = wire('input')->post;
    $this->source = wire('sanitizer')->url($post['source']);
    $this->target = wire('sanitizer')->url($post['target']);

    $this->parseWebmention();
  }

  /**
   * parse new webmention
   */
  public function parseWebmention() {
    if (empty($this->source)) {
      throw new WireException(__('Invalid source'));
    }

    if (empty($this->target)) {
      throw new WireException(__('Invalid target'));
    }

    if (!strpos($this->target, wire('config')->httpHost)) {
      throw new WireException(__('Invalid target'));
    }

    $data = \Mf2\fetch($this->source);
    if (count($data['items'])) {
      $this->result = \IndieWeb\comments\parse($data['items'][0], $this->source);
    }

    if (empty($this->result)) {
      throw new WireException(__('Probably spam'));
    }

    $this->registerWebmention();
  }

  /**
   * register new webmention
   */
  public function registerWebmention() {
    try {
      new Mention($this->result, $this->target);
    } catch(Exception $e) {
      throw new WireException(__('Webmention could not be registered'));
    }

    // redirect to target page
    wire('session')->redirect($this->target);
  }

}
