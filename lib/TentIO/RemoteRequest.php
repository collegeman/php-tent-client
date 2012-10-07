<?php
/**
 * Models a request to a remote Tent server, implements oAuth 2
 * protocol with HMAC.
 * @license MIT
 */

class TentIO_RemoteRequest extends TentIO_AbstractRequest {

  // a URI that represents the user to which this App instances is designated
  private $_entity;
  // configuration settings passed in the constructor, for reference purposes
  protected $_cfg;
  // an instance of AbstractTentHttp, facilitating connectivity
  protected $_http;
  // cached list of servers reported by sending GET to profile URIs
  protected $_servers;
  // the ID of this App, registered at $_entity's server(s)
  protected $_clientId;
  // the App ID (public), for MAC auth, registered at $_entity's server(s)
  protected $_appId;
  // the App Key (secret), for MAC auth, registered at $_entity's server(s)
  protected $_appKey;
  // $_entity's access token (public), for MAC auth
  protected $_userAccessToken;
  // $_entity's key (secret), for MAC auth
  protected $_userKey; 
  
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
    if (!self::isValidUrl($entity)) {
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
      'http' => new TentIO_CurlHttp()
    ), $config);

    if (!$config['http'] instanceof TentIO_AbstractHttp) {
      throw new Exception("HTTP class is invalid.");
    }

    $this->_http = $config['http'];
    
    if (!empty($config['servers'])) {
      $this->_servers = $config['servers'];
    } else if (!empty($config['server'])) {
      $this->_servers = array($config['server']);
    }

    if (!empty($config['mac_key_id'])) {
      $this->_appId = $config['mac_key_id'];
    }

    if (!empty($config['mac_key'])) {
      $this->_appKey = $config['mac_key'];
    }

    if (empty($config['client_id']) && !empty($config['id'])) {
      $config['client_id'] = $config['id'];
    }

    if (!empty($config['client_id'])) {
      $this->_clientId = $config['client_id'];
    }

    $this->_cfg = $config;
  }

  static function isValidUrl($url) {
    if ($filtered = filter_var($url, FILTER_VALIDATE_URL)) {
      if (strpos($filtered, 'http') === 0) {
        return $filtered;
      }
    }
    return false;
  }

  static function getProtocol() {
    $protocol = false;
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
      $protocol = ($_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
    } else if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] == 1)) {
      $protocol = 'https';
    } else {
      $protocol = 'http';
    }

    $protocol .= '://';
    return $protocol;
  }

  static function getHost() {
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
      $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
    } else {
      $host = $_SERVER['HTTP_HOST'];
    }
    return $host;
  }

  static function base64UrlDecode($input) {
    return base64_decode(strtr($input, '-_', '+/'));
  }

  static function base64UrlEncode($input) {
    $str = strtr(base64_encode($input), '+/', '-_');
    $str = str_replace('=', '', $str);
    return $str;
  }

  static function getCurrentUrl() {
    $protocol = self::getProtocol();
    $host = self::getHost();

    $currentUrl = $protocol.$host.$_SERVER['REQUEST_URI'];
    $parts = parse_url($currentUrl);

    $query = '';

    if (!empty($parts['query'])) {
      $params = explode('&', $parts['query']);
      $retained_params = array();
      foreach ($params as $param) {
        if (self::shouldBeRetained($param)) {
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

  static private function shouldBeRetained($param) {
    foreach(self::$DROP_QUERY_PARAMS as $name) {
      if (strpos($param, $name.'=') === 0) {
        return false;
      }
    }
    return true;
  }

  function getConfig() {
    return array(
      'name' => $this->_cfg['name'],
      'description' => $this->_cfg['description'],
      'url' => $this->_cfg['url'],
      'icon' => $this->_cfg['icon'],
      'redirect_uris' => $this->_cfg['redirect_uris'],
      'scopes' => $this->_cfg['scopes'],
      'id' => $this->getClientId(),
      'mac_key_id' => $this->getAppId(),
      'mac_key' => $this->getAppKey(),
      'mac_algorithm' => $this->_cfg['mac_algorithm']
    );      
  }

  function getEntity() {
    return $this->_entity;
  }

  function getClientId() {
    return $this->_clientId;
  }

  function setAppId($mac_key_id) {
    $this->_appId = $mac_key_id;
  }

  function setAppKey($mac_key) {
    $this->_appKey = $mac_key;
  }

  function setUserAccessToken($access_token) {
    $this->_userAccessToken = $access_token;
  }

  function setUserKey($mac_key) {
    $this->_userKey = $mac_key;
  }

  function getAppId() {
    return $this->_appId;
  }

  function getAppKey() {
    return $this->_appKey;
  }

  function getUserAccessToken() {
    return $this->_userAccessToken;
  }

  function getUserKey() {
    return $this->_userKey;
  }

  function getMethod() {}

  function getFunctionName() {}

  function getPathArgs() {}

}