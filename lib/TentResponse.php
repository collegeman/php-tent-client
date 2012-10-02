<?php
/**
 * Models the response from a Tent Server. 
 */
class TentResponse implements ArrayAccess, Iterator {

  protected $_rawBody;
  // json-decoded body of the response
  protected $_body;
  // when response is an file
  protected $_file;
  // Holds the HTTP status header code, e.g., 200
  protected $_statusCode;
  // When response includes an error message, holds that message
  protected $_errorMsg;
  // HTTP headers that should be in the response
  protected $_headers = array(
    'Content-Type' => 'application/vnd.tent.v0+json',
    'Cache-Control' => 'max-age=0, private, must-revalidate'
  );

  /**
   * Initialize a new TentResponse
   * @param array (optional) Headers
   */
  static function get($headers = null) {
    return new TentResponse($headers);
  }

  /**
   * Create an error response.
   * @param int HTTP status of error
   * @param string Error message
   * @param array headers in the response
   * @return TentResponse
   */
  static function error($httpStatusCode = 500, $message = null, $headers = null) {
    $R = self::get($headers);
    $R->_statusCode = $httpStatusCode;
    $R->_errorMsg = $message;
    return $R;
  }

  /**
   * Create a file response.
   * @param iTentFile file
   * @param array headers in the response
   * @return TentResponse
   */
  static function file(/* iTentFile */ $file, $httpStatusCode = 200, $headers = null) {
    $R = self::get($headers);
    $R->_statusCode = $httpStatusCode;
    $R->_file = $file;
    return $R;
  }

  static function create($body, $httpStatusCode = 200, $headers = null) {
    $R = self::get($headers);
    $R->_statusCode = $httpStatusCode;
    $R->_rawBody = $body;
    $R->_body = json_decode($R->_rawBody);
    return $R;
  }

  private function __construct($headers = null) {
    if (!is_null($headers)) {
      $this->_headers = array_merge($this->_headers, $headers);
    }
  }

  function isError() {
    return $this->getErrorCode() || !is_null($this->_errorMsg);
  }

  function getErrorCode() {
    return strpos($this->_statusCode, '2') !== 0 ? $this->_statusCode : false;
  }

  function getErrorMessage() {
    return $this->_errorMsg;
  }

  /**
   * Render the Response
   * @param bool Control header output; pass false to disable
   */
  function write($writeHeaders = true) {  
    $file = $this->getFile();
    if ($writeHeaders) {
      // if file is in response, override Content-Type
      if (!is_null($file)) {
        $this->setHeader('Content-Type', $file->getMimeType());
      }
      if ($this->isError()) {
        $this->setHeader('X-TENT-ERROR-MSG', $this->_errorMsg);
      } else {
        $this->setHeader('ETag', $this->getETag());
      }
      if (!isset($this->_headers['Status'])) {
        $this->_headers['Status'] = $this->_statusCode;
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

  function getRawBody() {
    return $this->_rawBody;
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

  function getHeader($name) {
    return isset($this->_headers[$name]) ? $this->_headers[$name] : false;
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
