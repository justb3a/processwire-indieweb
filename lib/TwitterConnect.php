<?php namespace IndieWeb;

require_once(__ROOT__.'/vendor/autoload.php');
require_once(__ROOT__.'/lib/AbstractTwitterConnect.php');

use \ProcessWire;
use \Abraham\TwitterOAuth\TwitterOAuth;
use \IndieWeb\AbstractTwitterConnect;

class TwitterConnect extends AbstractTwitterConnect {

  /**
   * string class name
   */
  const CLASS_NAME = 'IndieWeb';

  /**
   * integer Tweet length
   */
  const TWEET_LENGTH = 140;

  /**
   * integer Tweet link length
   * A URL of any length will be altered to 23 characters, even if the link itself is less than 23 characters long
   * more: https://support.twitter.com/articles/78124
   */
  const TWEET_LINK_LENGTH = 23;

  public function __construct($page) {
    $this->page = $page;
    $this->setConfigData();
    $this->setConnection();
    $this->doPost();
    // $this->getPost(); // not used atm
  }

  /**
  * Crop a text
  *
  * @param string $str
  * @param int $maxlen
  * @return string
  */
  public function truncate_str($str, $maxlen = 450) {
    if (strlen($str) > $maxlen) {
      $str = substr($str, 0, $maxlen);

      if (!preg_match('/[!?.]/', substr($str, -1, 1))) {
        preg_match_all('/[!?.]/', $str, $matches, PREG_OFFSET_CAPTURE);
        $end = end($matches[0])[1];

        if (!empty($end)) {
          $str = substr($str, 0, end($matches[0])[1] + 1);
        } elseif (substr($str, -1, 1) != ' ') {
          $str = substr($str, 0, strrpos($str, ' '));
        }
      }
    }

    return trim($str);
  }

  private function getPostContent() {
    $content = $this->page->iw_content;
    $contentLength = strlen($content);

    // get all links, get the length and substitute with TWITTER_LENGHT_LINK
    if (preg_match_all('/(http|https)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/', $content, $matches)) {
      if ($matches) {
        $linkLength = 0;
        foreach ($matches[0] as $link) {
          $linkLength = $linkLength + strlen($link);
        }
      }

      // remove link length from content length
      // add TWITTER_LENGHT_LINK for number of links
      $contentLength = $contentLength - $linkLength + count($matches[0]) * self::TWEET_LINK_LENGTH;
    }

    $append = "... ({$this->page->httpUrl})";
    $appendLength = self::TWEET_LINK_LENGTH + strlen('... ()');

    // shortn tweet if necessary
    if ($appendLength + $contentLength > self::TWEET_LENGTH) {
      $content = $this->truncate_str($this->page->iw_content, self::TWEET_LENGTH - $appendLength);
    } else {
      $append = substr($append, 3); // remove ...
    }

    return $content . $append;
  }

  public function doPost() {
    $medias = array();
    foreach ($this->page->iw_images as $img) {
      $media = $this->getConnection()->upload(
        'media/upload',
        array('media' => $img->filename)
      );
      $medias[] = $media->media_id_string;
    }

    $params = array('status' => $this->getPostContent());

    if (count($medias)) $params['media_ids'] = implode(',', $medias);

    if (
      !empty($this->page->iw_location_latitude) &&
      !empty($this->page->iw_location_longitude)
    ) {
      $params['lat'] = (float)$this->page->iw_location_latitude;
      $params['long'] = (float)$this->page->iw_location_longitude;
      $params['display_coordinates'] = true;
    }

    $result = $this->getConnection()->post('statuses/update', $params);

    // if coordinates are invalid – skip them
    if (
      $this->getConnection()->getLastHttpCode() != 200 &&
      isset($result->errors) &&
      count($result->errors) === 1 &&
      $result->errors[0]->code === 3
    ) {
      unset($params['lat']);
      unset($params['long']);
      unset($params['display_coordinates']);
      // resend without coordinates
      $result = $this->getConnection()->post('statuses/update', $params);
    }

    if ($this->getConnection()->getLastHttpCode() != 200) {
      $result = '';
    }

    if (is_object($result) && !empty($result->id_str)) {
      $this->page->iw_twitter_post_id = $result->id_str;
      $this->page->save();
    }
  }

  // not used atm
  public function getPost() {
    $result = $this->getConnection()->get(
      'statuses/show',
      array('id' => $this->page->iw_twitter_post_id, 'trim_user' => true)
    );

    if ($result) {
      $this->setRetweets($result->retweet_count);
      $this->setFavorites($result->favorite_count);

      if ($result->retweet_count > 0) {
        $retweeters = $this->getConnection()->get(
          'statuses/retweets',
          array('id' => $this->page->iw_twitter_post_id)
        );
        $this->setRetweeters($retweeters);
      }
    }
  }


}
