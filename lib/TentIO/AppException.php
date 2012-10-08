<?php
/**
 * Models exceptions thrown by the TentIO_App.
 * @license MIT
 */
class TentIO_AppException extends Exception {

  private $_response;

  function __construct($message, $code = null, $tentResponse = null) {
    if ($code instanceof TentIO_Response) {
      $tentResponse = $code;
      $code = $tentResponse->getErrorCode();
    }
    $this->_response = $tentResponse;
    parent::__construct($message, $code);
  }

  function __get($name) {
    if ($name === 'response') {
      return $this->_response;
    }
  }

}
