<?php
define('TENT_LOG_ERROR', 'error');
define('TENT_LOG_WARN', 'warning');
define('TENT_LOG_INFO', 'info');

/**
 * Models a persistent Log for any level of the Tent
 * Server framework to use. Implementations need only
 * define TentLog::log($level, $message) to provide
 * an input method. If some implementation other than
 * StderrTentLog should be used, set type using
 * TENT_LOG_TYPE constant, e.g., 'wordpress' or 'slim'.
 * @license MIT
 */
abstract class TentLog {

  private static $_instances;

  static function get($type = 'stderr') {
    if (defined('TENT_LOG_TYPE')) {
      $type = TENT_LOG_TYPE;
    }
    if (!isset(self::$_instances[$type])) {
      $class = ucwords($type).'TentLog';
      self::$_instances[new $class()];
    }
    return self::$_instances[$type];
  }

  abstract function log($message, $level = TENT_LOG_INFO);

  static function error($message) {
    return self::get()->log(TENT_LOG_ERROR, $message);
  }

  static function info($message) {
    return self::get()->log(TENT_LOG_INFO, $message);
  }

  static function warn($message) {
    return self::get()->log(TENT_LOG_WARN, $message);
  }

}

class StderrTentLog extends TentLog {

  function log($message, $level = TENT_LOG_INFO) {
    if ($level === TENT_LOG_ERROR) {
      $error_type = E_USER_ERROR;
    } else if ($level === TENT_LOG_WARN) {
      $error_type = E_USER_WARNING;
    } else {
      $error_type = E_USER_NOTICE;
    }
    return trigger_error($message, $error_type);
  } 

}