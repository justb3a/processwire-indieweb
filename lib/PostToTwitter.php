<?php

namespace Kfi\IndieWeb;

require_once(wire('config')->paths->IndieWeb . 'vendor/autoload.php');
use Abraham\TwitterOAuth\TwitterOAuth;

class PostToTwitter {

  /**
   * string class name
   */
  const CLASS_NAME = 'IndieWeb';

  public function __construct($page) {
    $this->doPost($page);
  }

  public function doPost($page) {
    $data = wire('modules')->getModuleConfigData(self::CLASS_NAME);
    $connection = new TwitterOAuth(
      $data['consumerKey'],
      $data['consumerSecret'],
      $data['accessToken'],
      $data['accessTokenSecret']
    );

    $medias = array();
    foreach ($page->iw_images as $img) {
      $media = $connection->upload('media/upload', array('media' => $img->filename));
      $medias[] = $media->media_id_string;
    }

    $params = array(
      'status' => $page->iw_content,
    );

    if (count($medias)) $params['media_ids'] = implode(',', $medias);

    if (!empty($page->iw_location_latitude) && !empty($page->iw_location_longitude)) {
      $params['lat'] = (float)$page->iw_location_latitude;
      $params['long'] = (float)$page->iw_location_longitude;
      $params['display_coordinates'] = true;
    }

    $result = $connection->post('statuses/update', $params);

    // if coordinates are invalid
    // skip them
    if (
      $connection->getLastHttpCode() != 200 &&
      isset($result->errors) &&
      count($result->errors) === 1 &&
      $result->errors[0]->code === 3
    ) {
      unset($params['lat']);
      unset($params['long']);
      unset($params['display_coordinates']);
      $result = $connection->post('statuses/update', $params);
    }

    if ($connection->getLastHttpCode() != 200) $result = '';

    if (is_object($result) && !empty($result->id_str)) {
      $page->iw_twitter_post_id = $result->id_str;
      $page->save();
    }
  }

}
