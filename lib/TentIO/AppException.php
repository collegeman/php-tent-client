<?php
/**
 * Models exceptions thrown by the TentIO_App.
 * @license MIT
 */
class TentIO_AppException extends Exception {

  private $_response;

  function __construct($message, $tentResponse) {
    $this->_response = $tentResponse;
    parent::__construct($message);
  }

  function __get($name) {
    if ($name === 'response') {
      return $this->_response;
    }
  }

}
