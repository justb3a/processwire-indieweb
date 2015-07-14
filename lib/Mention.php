<?php

namespace Kfi\IndieWeb;

use WireException;

class Mention {

  const TWITTER_LIKE = 'likes this.';
  const TWITTER_REPOST = 'reposts this.';
  const LIKE = 'like';
  const REPOST = 'repost';
  const REPLY = 'reply';

  public static $repeater = 'iw_mentions';

  public $data = null;

  /**
   * construct
   *
   * @param json $result
   * @param string $pageUrl
   */
  public function __construct($data, $pageUrl) {
    if (!is_array($data) || empty($data)) {
      throw new WireException(__('Invalid webmention'));
    }

    if (empty($data['url'])) {
      throw new WireException(__('No URL found'));
    }

    $this->data = $data;
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
    // ending slash? remove!
    $pageUrl = preg_replace('/\/$/', '', $pageUrl);
    $path = parse_url($pageUrl, PHP_URL_PATH);
    $pathFragments = explode('/', $path);
    $end = end($pathFragments);

    $date = new \DateTime($this->data['published']);
    $page = wire('pages')->get("name=$end");
    $newMention = $page->{self::$repeater}->getNew();

    $newMention->iw_type = $this->data['type'];
    $newMention->published = $date->format('Y-m-d H:i:s');
    $newMention->iw_post = $this->data['text'];
    $newMention->iw_url = $this->data['url'];
    $newMention->iw_author_name = $this->data['author']['name'];
    $newMention->iw_author_url = $this->data['author']['url'];

    // save to be able to add images
    $newMention->save();

    $newMention->iw_author_image->add($this->data['author']['photo']);
    $newMention->save();
  }

}
