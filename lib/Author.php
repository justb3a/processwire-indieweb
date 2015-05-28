<?php

namespace Kfi\IndieWeb;

class Author {

  public $mention = null;
  public $data = array();

  public function __construct($mention) {
    $this->mention = $mention;
    $this->data = $mention->data['author'];
    $this->data['relation'] = $this->mention->data['url'];
  }

}
