<?php namespace IndieWeb;

require_once(__ROOT__.'/vendor/autoload.php');
require_once(__ROOT__.'/lib/Mention.php');

use \IndieWeb\Mention;
use \ProcessWire\WireException;

class Webmentions extends \ProcessWire\Wire {

  public $source = null;
  public $target = null;
  public $result = '';

  /**
   * construct
   */
  public function __construct($source, $target) {
    $this->source = $source;
    $this->target = $target;
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
      $dataItem = $data['items'][0];
      foreach ($data['items'] as $item) {
        if ($item['type'][0] === 'h-entry' || $item['type'][0] === 'p-entry') {
          $dataItem = $item;
        }
      }

      $this->result = \IndieWeb\comments\parse($dataItem, $this->source);
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

  public function sendWebmention($page) {
    $html = $this->modules->get('TextformatterMarkdownExtra')->markdown($page->iw_content);
    $sourceURL = $page->httpUrl;

    $client = new \IndieWeb\MentionClient();
    $urls = $client->findOutgoingLinks($html);

    foreach ($urls as $targetURL) {
      $endpoint = $client->discoverWebmentionEndpoint($targetURL);
      if ($endpoint) {
        $result = $client->sendWebmention($sourceURL, $targetURL);
        $this->wire("log")->message("IndieWeb: sourceURL: $sourceURL, targetURL: $targetURL, result: " . json_encode($result));
      }
    }
  }

}
