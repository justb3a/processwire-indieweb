<?php

namespace Kfi\IndieWeb;

require_once(wire('config')->paths->IndieWeb . 'lib/AbstractTwitterConnect.php');
// require_once(wire('config')->paths->IndieWeb . 'vendor/autoload.php');
// use Abraham\TwitterOAuth\TwitterOAuth;

class TwitterConnect extends AbstractTwitterConnect {

  /**
   * string class name
   */
  const CLASS_NAME = 'IndieWeb';

  public function __construct($page = null) {
    // var_dump($this->configData);
    // $data = wire('modules')->getModuleConfigData(self::CLASS_NAME);
    // $this->connection = new TwitterOAuth(
    //   $data['consumerKey'],
    //   $data['consumerSecret'],
    //   $data['accessToken'],
    //   $data['accessTokenSecret']
    // );
    //
    // $this->page = $page;

    // if (empty($note)) {
      // $this->doPost();
    // } else {
    // if (is_null($this->page)) {
    $this->page = wire('page');
    $this->setConfigData();
    $this->setConnection();
    $this->getPost();
    // }

    // }
  }

  public function getPost() {
    $result = $this->getConnection()->get('statuses/show', array('id' => $this->page->iw_twitter_post_id, 'trim_user' => true));
    $this->setRetweets($result->retweet_count);
    $this->setFavorites($result->favorite_count);

    if ($result->retweet_count > 0) {
      $retweeters = $this->getConnection()->get('statuses/retweets', array('id' => $this->page->iw_twitter_post_id));
      $this->setRetweeters($retweeters);
    }
  }

  public function doPost() {
    $medias = array();
    foreach ($this->page->iw_images as $img) {
      $media = $this->connection->upload('media/upload', array('media' => $img->filename));
      $medias[] = $media->media_id_string;
    }

    $params = array(
      'status' => $this->page->iw_content,
    );

    if (count($medias)) $params['media_ids'] = implode(',', $medias);

    if (!empty($this->page->iw_location_latitude) && !empty($this->page->iw_location_longitude)) {
      $params['lat'] = (float)$this->page->iw_location_latitude;
      $params['long'] = (float)$this->page->iw_location_longitude;
      $params['display_coordinates'] = true;
    }

    $result = $this->connection->post('statuses/update', $params);

    // if coordinates are invalid
    // skip them
    if (
      $this->connection->getLastHttpCode() != 200 &&
      isset($result->errors) &&
      count($result->errors) === 1 &&
      $result->errors[0]->code === 3
    ) {
      unset($params['lat']);
      unset($params['long']);
      unset($params['display_coordinates']);
      $result = $this->connection->post('statuses/update', $params);
    }

    if ($this->connection->getLastHttpCode() != 200) $result = '';

    if (is_object($result) && !empty($result->id_str)) {
      $this->page->iw_twitter_post_id = $result->id_str;
      $this->page->save();
    }
  }

}

