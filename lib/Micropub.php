<?php

namespace Kfi\IndieWeb;
use WireException;
use WirePermissionException;

class Micropub {

  protected static $tokenEndpoint = 'https://tokens.indieauth.com/token';

  protected static $tmpls = array(
    'list' => 'iw_notes',
    'single' => 'iw_note'
  );

  public function __construct() {
    $this->start();
  }

  public function start() {
    $site = 'http://' . wire('config')->httpHost . '/';
    $post = wire('input')->post;

    $_HEADERS = array();
    foreach ($this->getallheaders() as $name => $value) {
      $_HEADERS[$name] = $value;
    }

    if (!isset($_HEADERS['Authorization'])) {
      header($_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized');
      throw new WirePermissionException(__('Missing Authorization header.'));
    }

    if (!isset($post['h'])) {
      header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
      throw new WireException(__('Missing *h* value.'));
    }

    $options = array(
      CURLOPT_URL => self::$tokenEndpoint,
      CURLOPT_HTTPGET => TRUE,
      CURLOPT_USERAGENT => $site,
      CURLOPT_TIMEOUT => 5,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_HEADER => FALSE,
      CURLOPT_HTTPHEADER => array(
        'Content-type: application/x-www-form-urlencoded',
        'Authorization: ' . $_HEADERS['Authorization']
      )
    );

    $curl = curl_init();
    curl_setopt_array($curl, $options);
    $source = curl_exec($curl);
    curl_close($curl);

    parse_str($source, $values);

    if (!isset($values['me'])) {
      header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
      throw new WireException(__('Missing *me* value in authentication token.'));
    }

    if (!isset($values['scope'])) {
      header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
      throw new WireException(__('Missing *scope* value in authentication token.'));
    }

    if (substr($values['me'], -1) != '/') $values['me'] .= '/';

    if (strtolower($values['me']) != strtolower($site)) {
      header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
      throw new WirePermissionException(__('Mismatching *me* value in authentication token.'));
    }

    if (!stristr($values['scope'], 'post')) {
      header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
      throw new WirePermissionException(__('Missing *post* value in *scope*.'));
    }

    if (!isset($post['content'])) {
      header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
      throw new WireException(__('Missing *content* value.'));
    }

    // create new page object and save it to make image fields available for uploading
    // first save page - status unpublished to invoke hook to post to twitter via quill
    $p = new \Page();
    $p->template = self::$tmpls['single'];
    $p->parent = wire('pages')->get('template=' . self::$tmpls['list']);

    $p->iw_category = $post['category'];
    $p->iw_pubdate = date('Y-m-d H:i');
    $p->iw_content = $post['content'];

    if ($post['location']) $p->iw_location = $post['location'];
    if ($post['place_name']) $p->iw_place_name = $post['place_name'];

    $p->addStatus(Page::statusUnpublished);
    $p->save();

    // adding images
    // $img_path = $_FILES['photo']['tmp_name'];
    // $img_name = $_FILES['photo']['name'];
    //
    // // $_FILES array singular vs. multiple
    // // normalize by converting single to array prior to looping.
    // // http://php.net/manual/en/reserved.variables.files.php
    //
    // if (!(is_array($img_path))){
    //   $img_path = array($img_path);
    //   $img_name = array($img_name);
    // }
    //
    // for ($i=0; $i < count($img_path); $i++){
    //   $p->images->add($img_path[$i]);
    // }
    //

    $p->removeStatus(Page::statusUnpublished);
    $p->save();


    header($_SERVER['SERVER_PROTOCOL'] . ' 201 Created');
    header('Location: ' . $site);
  }

  public function getallheaders() {
    $headers = '';
    foreach ($_SERVER as $name => $value) {
      if (substr($name, 0, 5) == 'HTTP_') {
        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
      }
    }
    return $headers;
  }

}
