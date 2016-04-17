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

  public function __construct($page) {
    $this->page = $page;
    $this->setConfigData();
    $this->setConnection();
    $this->doPost();
    // $this->getPost(); // not used atm
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

    $params = array(
      'status' => $this->page->iw_content,
    );

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

    // if coordinates are invalid
    // skip them
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
