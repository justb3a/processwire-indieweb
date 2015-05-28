<?php

namespace Kfi\IndieWeb;

require_once(wire('config')->paths->IndieWeb . 'vendor/autoload.php');
use Abraham\TwitterOAuth\TwitterOAuth;

abstract class AbstractTwitterConnect {

  const CLASS_NAME = 'IndieWeb';

  private $retweets;
  private $connection;
  private $configData;

  public function __construct() {
    // $this->getPost();
  }

  protected function setRetweets($retweets) {
    $this->retweets = $retweets;
  }

  public function getRetweets() {
    return $this->retweets;
  }

  protected function setFavorites($favorites) {
    $this->favorites = $favorites;
  }

  public function getFavorites() {
    return $this->favorites;
  }

  protected function setConfigData() {
    $this->configData = wire('modules')->getModuleConfigData(self::CLASS_NAME);
  }

  public function getConfigData() {
    return $this->configData;
  }

  protected function setConnection() {
    $this->connection = new TwitterOAuth(
      $this->configData['consumerKey'],
      $this->configData['consumerSecret'],
      $this->configData['accessToken'],
      $this->configData['accessTokenSecret']
    );
  }

  public function getConnection() {
    return $this->connection;
  }

  protected function setRetweeters($retweeters) {
    $this->retweeters = $retweeters;
  }

  public function getRetweeters() {
    return $this->retweeters;
  }
}
