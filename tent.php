<?php


/**
 * A simple client for signing requests to a Tent Server.
 */
abstract class AbstractTentClient {

  function __construct($profileUri, $appToken, $appSecret, $accessToken = false, $accessSecret = false) {

  }

  abstract function request($url, $options = array());

}

class CurlTentClient extends AbstractTentClient {

  function request($url, $options = array()) {

  }

}


