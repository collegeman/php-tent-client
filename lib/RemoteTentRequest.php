<?php
/**
 * Models a request to a remote Tent server, implements oAuth 2
 * protocol with HMAC.
 * @license MIT
 */
class RemoteTentRequest extends AbstractTentRequest {

  /**
   * @param string Tent Entity ID, e.g., http://collegeman.tent.is
   * @param array Configuration options
   */
  function __construct($entity, $config = array()) {
    $config = array_merge(array(
      'client' => new CurlTentClient()
    ), $config);

    $this->cfg = $config;
  }

  static function autoload($className) {
    if (strpos($className, 'Tent') !== false) {
      require(dirname(__FILE__).'/'.$className.'.php');
    }
  }

  /**
   * Do a request.
   * @return AbstractTentResponse
   */
  function api($path, $method = 'GET', $body = null) {
    $this->_method = strtoupper($method);
    $this->setBody($body);
  }

  function getLoginUrl() {

  }

  function getMethod() {

  }

  function getFunctionName() {

  }

  function getPathArgs() {

  }

}

spl_autoload_register(array('RemoteTentRequest', 'autoload'));