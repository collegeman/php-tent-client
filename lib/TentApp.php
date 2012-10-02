<?php
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
    $_SESSION[$this->_entity][$name] = $value;
  }

  function get($name, $default = false) {
    if (!array_key_exists($this->_entity, $_SESSION)) {
      return $default;
    }
    if (!array_key_exists($name, $_SESSION[$this->_entity])) {
      return $default;
    }
    $value = $_SESSION[$this->_entity][$name];
    return is_null($value) ? $default : $value;    
  }

  function destroySession() {
    unset($_SESSION[$this->_entity]);
  }

  /**
   * Do a request.
   * @return AbstractTentResponse
   */
  function api($path, $method = 'GET', $config = array()) {
    if (!$this->discover()) {
      return TentResponse::error(404, "No servers available for [{$this->_entity}]");
    }

    $this->_method = strtoupper($method);
    if (isset($config['body'])) {
      $this->setBody($config['body']);
      unset($config['body']);
    } else {
      $this->setBody(null);
    }

    return $this->_client->send($this, $config);
  }

  /**
   * Perform discovery on the given Profile entity
   * @return array Server URLs
   */
  function discover() {
    if (is_null($this->_discovery)) {
      $this->_discovery = $this->_client->request($this->_entity, array('method' => 'HEAD'));
      if (!$this->_discovery->isError()) {
        if ($Link = $this->_discovery->getHeader('Link')) {
          foreach(explode(',', $Link) as $link) {
            if (preg_match('#<(.*?)>; rel="https://tent.io/rels/profile"#', trim($link), $matches)) {
              $this->_profiles[] = $matches[1];
            }
          }
        } else {
          // look in the body
        }
      }
    }
    
    if (is_null($this->_servers)) {
      $this->_servers = array();
      foreach($this->_profiles as $i => $link) {
        $profile = $this->_client->request($link);
        if (!$profile->isError()) {
          $this->_servers = array_merge($this->_servers, $profile->{self::$PROFILE_INFO_TYPES['core']}->servers);      
        }
      }
    }

    return $this->_servers;
  }

  /**
   * Get app registration; register if not registered yet.
   * @param $config['redirect_uris'] Array of callback URLs to satisfy oAuth workflow
   * @param $config['scopes'] Array of all the scopes this application will use
   * @see http://tent.io/docs/app-auth
   */ 
  function register($config = array()) {
    // already registered?
    if (isset($this->_cfg['mac_key'])) {
      return TentResponse::create(json_encode($this->_cfg['mac_key']), 200);
    }

    if (!$servers = $this->discover()) {
      return TentResponse::error(404, "No servers available for [{$this->_entity}]");
    }

    $config = array_merge(array(
      'name' => $this->_cfg['name'],
      'description' => $this->_cfg['description'],
      'url' => $this->getCurrentUrl(),
      'icon' => $this->getCurrentUrl().'/icon.png',
      'scopes' => $this->_cfg['scopes'],
      'redirect_uris' => $this->_cfg['redirect_uris']
    ), $config);

    $available_servers = $servers;
    do {
      $server = array_shift($available_servers);
      $response = $this->_client->request($server.'/apps', array(
        'method' => 'POST',
        'body' => $config,
        'headers' => array(
          'Content-Type: application/vnd.tent.v0+json',
          'Accept: application/vnd.tent.v0+json'
        )
      ));
    } while ($response->isError() && $available_servers);

    if (!$response->isError()) {
      $this->_cfg['id'] = $response->id;
      $this->_cfg['mac_key_id'] = $response->mac_key_id;
      $this->_cfg['mac_key'] = $response->mac_key;
      $this->_cfg['mac_algorithm'] = $response->mac_algorithm;
      $this->_cfg['servers'] = $servers;
    }

    return $this->_cfg;

    return $response;
  }

}