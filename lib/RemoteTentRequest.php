<?php
/**
 * Models a request to a remote Tent server, implements oAuth 2
 * protocol with HMAC.
 * @license MIT
 */

function tent_autoload($className) {
  if (strpos($className, 'Tent') !== false) {
    require(dirname(__FILE__).'/'.$className.'.php');
  }
}

spl_autoload_register('tent_autoload');

class RemoteTentRequest extends AbstractTentRequest {

  protected $_entity;
  protected $_cfg;
  protected $_client;
  protected $_discovery;
  protected $_profiles;
  protected $_servers;

  protected static $PROFILE_INFO_TYPES = array(
    'core' => 'https://tent.io/types/info/core/v0.1.0',
    'basic' => 'https://tent.io/types/info/basic/v0.1.0',
  );

  protected static $DROP_QUERY_PARAMS = array(
    'code',
    'state',
    'error',
    'error_description'
  );

  /**
   * @param string Tent Entity ID, e.g., http://collegeman.tent.is
   * @param array Configuration options
   */
  function __construct($entity, $config = array()) {
    if (!filter_var($entity, FILTER_VALIDATE_URL)) {
      throw new Exception("Invalid Entity URI: {$entity}");
    }

    $this->_entity = $entity;
    
    $config = array_merge(array(
      'name' => 'php-tent-client',
      'description' => 'The PHP client library for Tent.io',
      'tent_profile_info_types' => 'all',
      'tent_post_types' => 'all',
      'redirect_uris' => array(
        $this->getCurrentUrl()
      ),
      'scopes' => array(
        'read_profile' => 'Read profile sections listed in the profile_info parameter',
        'write_profile' => 'Read and write profile sections listed in the profile_info parameter',
        'read_followers' => 'Read follower list',
        'write_followers' => 'Read follower list and block followers',
        'read_followings' => 'Read followings list',
        'write_followings' => 'Read followings list and follow new entities',
        'read_posts' => 'Read posts with types listed in the post_types parameter',
        'write_posts' => 'Read and publish posts with types listed in the post_types parameter'
      ),
      'client' => new CurlTentClient()
    ), $config);

    if (!$config['client'] instanceof AbstractTentClient) {
      throw new Exception("Client is invalid");
    }

    $this->_client = $config['client'];
    unset($config['client']);

    if (!empty($config['servers'])) {
      $this->_servers = $config['servers'];
      unset($config['servers']);
    } else if (!empty($config['server'])) {
      $this->_servers = array($config['server']);
      unset($config['server']);
    }

    $this->_cfg = $config;
  }

  function getCurrentUrl() {
    $protocol = false;
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
      $protocol = ($_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
    } else if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] == 1)) {
      $protocol = 'https';
    } else {
      $protocol = 'http';
    }

    $protocol .= '://';

    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
      $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
    } else {
      $host = $_SERVER['HTTP_HOST'];
    }

    $currentUrl = $protocol.$host.$_SERVER['REQUEST_URI'];
    $parts = parse_url($currentUrl);

    $query = '';

    if (!empty($parts['query'])) {
      $params = explode('&', $parts['query']);
      $retained_params = array();
      foreach ($params as $param) {
        if ($this->shouldBeRetained($param)) {
          $retained_params[] = $param;
        }
      }

      if (!empty($retained_params)) {
        $query = '?'.implode($retained_params, '&');
      }
    }

    $port =
      isset($parts['port']) &&
      (($protocol === 'http://' && $parts['port'] !== 80) ||
       ($protocol === 'https://' && $parts['port'] !== 443))
      ? ':' . $parts['port'] : '';

    return $protocol . $parts['host'] . $port . $parts['path'] . $query;
  }

  private function shouldBeRetained($param) {
    foreach(self::$DROP_QUERY_PARAMS as $name) {
      if (strpos($param, $name.'=') === 0) {
        return false;
      }
    }
    return true;
  }

  

  function getMethod() {}

  function getFunctionName() {}

  function getPathArgs() {}

}