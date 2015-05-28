<?php

namespace Kfi\IndieWeb;

require_once(wire('config')->paths->IndieWeb . 'lib/Author.php');
use WireException;

class Mention {

  const TWITTER_LIKE = 'likes this.';
  const TWITTER_REPOST = 'reposts this.';
  const LIKE = 'like';
  const REPOST = 'repost';
  const REPLY = 'reply';

  public $data = null;
  public $author = null;

  /**
   * construct
   *
   * @param json $result
   * @param string $pageUrl
   */
  public function __construct($result, $pageUrl) {
    $data = json_decode($result);

    if (!is_array($data) or empty($data)) {
      throw new WireException(__('Invalid webmention'));
    }

    if (empty($data['url'])) {
      throw new WireException(__('No URL found'));
    }

    $this->data = $data;
    $this->author = new Author($this);
    $this->convertTwitterType();
    $this->getTwitterPostId();
    $this->saveMention($pageUrl);
  }

  /**
   * convert twitter type
   */
  public function convertTwitterType() {
    if (preg_match('!https:\/\/twitter.com\/(.*?)\/status!', $this->data['url'])) {
      if (!empty($this->data['name'])) {
        switch ($this->data['name']) {
          case self::TWITTER_LIKE:
            $this->data['type'] = self::LIKE;
            break;
          case self::TWITTER_REPOST:
            $this->data['type'] = self::REPOST;
            break;
        }
      } elseif ($this->data['name'] === false) {
        $this->data['type'] = self::REPLY;
      }
    }
  }

  /**
   * get twitter post id
   */
  public function getTwitterPostId() {
    $twitterPostId = preg_replace(
      '/https:\/\/twitter.com\/(.*?)\/status\//',
      '',
      $this->data['url']
    );

    if (!empty($twitterPostId)) {
      $this->data['twitterPostId'] = $twitterPostId;
    }
  }

  /**
   * save Webmention
   *
   * @param string $pageUrl
   */
  public function saveMention($pageUrl) {
    $page = wire('pages')->get($pageUrl);

    // @todo: save mentions
    // create repeater for favorites and reposts
    // create repeater for replys / mentions
  }

}
