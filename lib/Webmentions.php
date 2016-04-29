<?php namespace IndieWeb;

require_once(__ROOT__.'/vendor/autoload.php');
require_once(__ROOT__.'/lib/Mention.php');

use \IndieWeb\Mention;
use \ProcessWire\WireException;

class Webmentions extends \ProcessWire\Wire {

  public $source = null;
  public $target = null; // own site link
  public $result = '';

  /**
   * construct
   */
  public function __construct() {
    $post = $this->wire('input')->post;
    $this->source = $this->wire('sanitizer')->url($post['source']);
    $this->target = $this->wire('sanitizer')->url($post['target']);

    $this->parseWebmention();
  }

  /**
   * parse new webmention
   */
  public function parseWebmention() {
    if (empty($this->source)) {
      throw new WireException($this->_('Invalid source'));
    }

    if (empty($this->target)) {
      throw new WireException($this->_('Invalid target'));
    }

    if (!strpos($this->target, $this->wire('config')->httpHost)) {
      throw new WireException($this->_('Invalid target'));
    }

    $data = \Mf2\fetch($this->source);
    if (count($data['items'])) {
      $this->result = \IndieWeb\comments\parse($data['items'][0], $this->source);
    }

    if (empty($this->result)) {
      throw new WireException($this->_('Probably spam'));
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
      throw new WireException($this->_('Webmention could not be registered'));
    }

    // redirect to target page
    $this->wire('session')->redirect($this->target);
  }

}
