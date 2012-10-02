<?php
/**
 * Models the response from a Tent Server. Subclasses
 * need only implement AbstractTentResponse::setStatusHeader,
 * but may also choose to override AbstractTentResponse::write.
 */
abstract class TentResponse implements ArrayAccess, Iterator {

  // body of the response - will be JSON encoded
  protected $_body;
  // when response is an file
  protected $_file;
  // When response is an error, holds the code of that error
  protected $_errorCode;
  // When response includes an error message, holds that message
  protected $_errorMsg;
  // HTTP headers that should be in the response
  protected $_headers = array(
    'Content-Type' => 'application/vnd.tent.v0+json',
    'Cache-Control' => 'max-age=0, private, must-revalidate'
  );

  /**
   * Initialize a new AbstractTentResponse implementation
   * @param array (optional) Headers
   * @param string (optinoal) Implementing class; overidden by TENT_RESPONSE_TYPE
   */
  static function get($headers = null, $type = null) {
    if (defined('TENT_RESPONSE_TYPE')) {
      $type = TENT_RESPONSE_TYPE;
    }
    $class = ucwords($type).'TentResponse';
    return new $class($headers);
  }

  /**
   * Create an error response.
   */
  static function error($code, $message = null, $headers = null) {
    $R = self::get($headers);
    $R->_errorCode = $code;
    $R->_errorMsg = $message;
    return $R;
  }

  static function file(/* iTentFile */ $file, $headers = null) {
    $R = self::get($headers);
    $R->_file = $file;
    return $R;
  }

  static function create($body, $headers = null) {
    $R = self::get($headers);
    $R->_body = $body;
    return $R;
  }

  private function __construct($headers = null) {
    if (!is_null($headers)) {
      $this->_headers = array_merge($this->_headers, $headers);
    }
  }

  function isError() {
    return !is_null($this->errorCode) || !is_null($this->errorMsg);
  }

  function getErrorCode() {
    return $this->_errorCode;
  }

  function getErrorMessage() {
    return $this->_errorMsg;
  }

  abstract function setStatusHeader($code);

  /**
   * Render the Response
   * @param bool Control header output; pass false to disable
   */
  function write($setHeaders = true) {  
    $file = $this->getFile();
    if ($setHeaders) {
      // if file is in response, override Content-Type
      if (!is_null($file)) {
        $this->setHeader('Content-Type', $file->getMimeType());
      }
      if ($this->isError()) {
        $this->setStatusHeader($this->_errorCode);
        $this->setHeader('X-TENT-ERROR-MSG', $this->_errorMsg);
      } else {
        $this->setStatusHeader(200);
        $this->setHeader('ETag', $this->getETag());
      }
      foreach($this->getHeaders() as $name => $value) {
        header("{$name}: {$value}");
      }
    }
    echo $this->getEncodedBody();
  }

  private $_eTag;
  function getETag() {
    if (is_null($this->_eTag)) {
      if ($file = $this->getFile()) {
        $this->_eTag = md5($file);
      } else {
        $this->_eTag = md5($this->getEncodedBody());
      }
    }
    return $this->_eTag;
  }

  function getEncodedBody() {
    return json_encode($this->_body);
  }

  function getBody() {
    return $this->_body;
  }

  function setBody($body) {
    $this->_body = $body;
  }

  function setFile($file) {
    $this->_file = $file;
  }

  function getFile() {
    return $this->_file;
  }

  function getHeaders() {
    return $this->_headers;
  }

  function setHeader($name, $value = '') {
    $this->_headers[$name] = $value;
  }

  function offsetExists($offset) {
    return isset($this->_body[$offset]);
  }

  function offsetGet($offset) {
    return $this->_body[$offset];
  }

  function offsetSet($offset, $value) {
    throw new Exception("TentResponse body is immutable.");
  }

  function offsetUnset($offset) {
    throw new Exception("TentResponse body is immutable.");
  }

  function current() {
    return current($this->_body);
  }
  
  function key() {
    return key($this->_body);
  }
  
  function next() {
    return next($this->_body);
  }
  
  function rewind() {
    reset($this->_body);
  }
  
  function valid() {
    return isset($this->_body[$this->key()]);
  }

  /**
   * Get an arg from the response body.
   */
  function __get($name) {
    if (is_array($this->_body)) {
      $current = $this->current();
      return $current->{$name};
    } else {
      return $this->_body->{$name};
    }
  }


}
