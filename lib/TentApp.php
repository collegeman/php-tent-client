<?php

function tent_autoload($className) {
  if (strpos($className, 'Tent') !== false) {
    require(dirname(__FILE__).'/'.$className.'.php');
  }
}

spl_autoload_register('tent_autoload');

/**
 * An implementation of BaseTentApp that uses the PHP session
 * for storing user-specific tokens.
 * @license MIT
 */
class TentApp extends BaseTentApp {

  function __construct($url, $config = array()) {
    session_start();
    if (!session_id()) {
      throw new Exception("Failed to start session");
    }
    parent::__construct($url, $config);
  }

  function set($name, $value = null) {
    $_SESSION[$this->getEntity()][$name] = $value;
  }

  function delete($name) {
    unset($_SESSION[$name]);
  }

  function get($name, $default = false) {
    if (!array_key_exists($this->getEntity(), $_SESSION)) {
      return $default;
    }
    if (!array_key_exists($name, $_SESSION[$this->getEntity()])) {
      return $default;
    }
    $value = $_SESSION[$this->getEntity()][$name];
    return is_null($value) ? $default : $value;    
  }

  function destroySession() {
    unset($_SESSION[$this->getEntity()]);
  }

}