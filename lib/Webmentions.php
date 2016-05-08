<?php namespace IndieWeb;

require_once(__ROOT__.'/vendor/autoload.php');
require_once(__ROOT__.'/lib/Mention.php');

use \IndieWeb\Mention;
use \ProcessWire\WireException;

class Webmentions extends \ProcessWire\Wire {

  public $source = null;
  public $target = null;
  public $httpPage = null;
  public $result = '';

  /**
   * construct
   */
  public function __construct($source, $target, $httpPage = null) {
    $this->source = $source;
    $this->target = $target;
    $this->httpPage = $httpPage ? $httpPage : $this->page;
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

      // check whether the webmention also contains a "like"
      $doubleTheLike = false;
      if (array_key_exists('like-of', $dataItem['properties'])) {
        if (in_array($this->target, $dataItem['properties']['like-of'])) {
          $doubleTheLike = true;
        }
      }

      $this->result = \IndieWeb\comments\parse($dataItem, $this->source);
    }

    if (empty($this->result)) throw new WireException($this->_('Probably spam'));

    $this->registerWebmention($doubleTheLike);
  }

  /**
   * register new webmention
   */
  public function registerWebmention($doubleTheLike) {
    try {
      new Mention($this->result, $this->target, $doubleTheLike);
    } catch(Exception $e) {
      throw new WireException($this->_('Webmention could not be registered'));
    }

    // redirect to target page
    $this->wire('session')->redirect($this->target);
  }

  public function sendWebmention() {
    $html = $this->modules->get('TextformatterMarkdownExtra')->markdown($this->httpPage->iw_content);
    $sourceURL = $this->httpPage->httpUrl;

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
