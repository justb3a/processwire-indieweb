<?php namespace IndieWeb;

use \ProcessWire\WireException;

class Mention extends \ProcessWire\Wire {

  const TWITTER_LIKE = 'likes this.';
  const TWITTER_REPOST = 'reposts this.';
  const LIKE = 1;
  const REPOST = 3;
  const MENTION = 4;

  public static $repeater = 'iw_mentions';

  public $data = null;

  /**
   * construct
   *
   * @param json $result
   * @param string $pageUrl
   */
  public function __construct($data, $pageUrl, $doubleTheLike) {
    if (!is_array($data) || empty($data)) {
      throw new WireException($this->_('Invalid webmention'));
    }

    if (!$data['author']['url']) {
      throw new WireException($this->_('No URL found'));
    }

    $this->data = $data;
    $this->convertTwitterType();
    $this->getTwitterPostId();
    $this->saveMention($pageUrl);

    if ($this->data['type'] !== self::LIKE && $doubleTheLike) {
      $this->data['type'] = self::LIKE;
      $this->saveMention($pageUrl);
    }
  }

  /**
   * convert twitter type
   */
  public function convertTwitterType() {
    $this->wire('log')->message('IndieWeb: ' . json_encode($this->data));

    if (preg_match('/https:\/\/twitter.com\//', $this->data['url'])) {
      if (
        preg_match('/https:\/\/twitter.com\/(.*?)\/status\/\d*#favorited-by-\d*$/', $this->data['url'])
        || !$this->data['text']
      ) {
        $this->data['type'] = self::LIKE;
      } elseif (preg_match('/^RT\s/', $this->data['text'])) {
        $this->data['type'] = self::REPOST;
      } else {
        $this->data['type'] = self::MENTION;
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
    $page = $this->wire('pages')->get("name=$end");
    if ($page->{self::$repeater}) {

      $filter = "template=repeater_iw_mentions,iw_url={$this->data['url']},iw_type={$this->data['type']}";
      $existingMentions = $page->{self::$repeater}->filter($filter);

      // does the specific mention already exist?
      if ($existingMentions->count() === 0) {
        // create new one
        $mention = $page->{self::$repeater}->getNew();

        $mention->iw_type = $this->data['type'];
        $mention->iw_url = $this->data['url'];
        $mention->published = $date->format('Y-m-d H:i:s');
      } else {
        // pick existing
        $mention = $existingMentions->first();
        $mention->setOutputFormatting(false);
      }

      $mention->iw_post = $this->data['text'];
      $mention->iw_author_name = $this->data['author']['name'];
      $mention->iw_author_url = $this->data['author']['url'];

      // save to be able to add/update author image
      $mention->save();

      if ($this->data['author'] && $this->data['author']['photo']) {
        $mention->iw_author_image->deleteAll();
        $mention->iw_author_image = $this->data['author']['photo'];
        $mention->save();
      }
    }
  }

}
