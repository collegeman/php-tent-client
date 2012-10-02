<?php
/**
 * Models a request to a Tent server.
 * @license MIT
 */
abstract class AbstractTentRequest implements ArrayAccess, Iterator {

  protected $_body;

  function setBody($body) {
    $this->_body = $body;
  }

  /**
   * @return HTTP method for request: get, post, put, delete.
   */
  abstract function getMethod();

  /**
   * @return The Tent Server function that should be executed.
   */
  abstract function getFunctionName();

  /**
   * @return array Arguments, if any, that should be passed into the requested Tent Server function.
   */
  abstract function getPathArgs();

  /**
   * Get an arg from the request body.
   */
  function __get($name) {
    if (is_array($this->_body)) {
      $current = $this->current();
      return $current->{$name};
    } else {
      return $this->_body->{$name};
    }
  }

  function offsetExists($offset) {
    return isset($this->_body[$offset]);
  }

  function offsetGet($offset) {
    return $this->_body[$offset];
  }

  function offsetSet($offset, $value) {
    $this->_body[$offset] = $value;
  }

  function offsetUnset($offset) {
    unset($this->_body[$offset]);
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

}