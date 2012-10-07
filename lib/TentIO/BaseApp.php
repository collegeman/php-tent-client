<?php
/**
 * Models a TentApp. This is a thin wrapper around 
 * the RemoteTentRequest class, facilitating the storage
 * of user-specific tokens, e.g., user access token and
 * mac key, as well as the CSRF-defending state arg.
 * @license MIT
 */
abstract class TentIO_BaseApp extends TentIO_RemoteRequest {

  // TentIO_Response, the result of sending HEAD request to $_entity
  protected $_discovery;
  // cached list of profiles reported through discovery
  protected $_profiles;

  /**
   * Store a setting for this Entity, for this App.
   * @param string The arg name
   * @param mixed The arg value
   * @see TentApp for a session-based example
   */
  abstract function set($name, $value = null);

  /**
   * Retrieve a setting for this Entity, for this App.
   * @param string The arg name
   * @param mixed A default value; defaults to false
   * @see TentApp for a session-based example
   */
  abstract function get($name, $default = false);

  /**
   * Delete a setting for this Entity, for this App.
   * @param string The arg name
   */
  abstract function delete($name);


  /**
   * Wipe-out any stored user tokens for the current target Entity.
   * @see TentApp for a session-based example
   */
  abstract function destroySession();

  function getUserKey() {
    if ($access_token = $this->getUserAccessToken()) {
      return $this->get('mac_key');
    }
  }

  /**
   * Get the user access token made available to this App.
   * This function will look first to the persistent copy
   * of the user's access token; finding none, it will then
   * look to the current request for a "code" arg. Finding
   * this, an access token request will be made. If this is
   * successful, the access token will be stored.
   * @return string
   * @throws TentIO_AppException If no servers are available for Entity.
   * @throws TentIO_AppException If access token request fails.
   */
  function getUserAccessToken() {
    if ($access_token = $this->get('access_token')) {
      return $access_token;
    }

    if (!empty($_REQUEST['code']) && !empty($_REQUEST['state'])) {
      return $this->getUserAccessTokenFromCode();
    } else {
      throw new TentIO_AppException("No user access token available", 500);
    }   
  }

  protected function getUserAccessTokenFromCode() {
    if ($this->get('state') !== $_REQUEST['state']) {
      $this->delete('state');
      throw new TentIO_AppException("Invalid CSRF state", 400);
    }

    $response = $this->api('/apps/'.$this->getClientId().'/authorizations', array(
      'code' => $_REQUEST['code'],
      'token_type' => 'mac'
    ));

    if ($response->isError()) {
      throw new TentIO_AppException("Invalid access token request", $response);
    } else {
      $this->set('access_token', $response->access_token);
      $this->set('mac_key', $response->mac_key);
      $this->set('mac_algorithm', $response->mac_algorithm);
      $this->set('token_type', $response->token_type);
    }

    $this->delete('state'); 

    return $this->get('access_token');
  }

  protected function getNewNonce() {
    return md5($this->getEntity().uniqid());
  }

  /**
   * Perform some request of the Tent server that, if successful,
   * denotes a properly authenticated session.
   */
  function isLoggedIn() {
    $this->api('/posts?limit=1');
    return true;
  }

  /**
   * Do a request.
   * @return TentIO_Response
   * @throws TentIO_AppException
   */
  function api($path, $method = 'GET', $data = array()) {
    if (!$this->discover()) {
      throw new TentIO_AppException(sprintf("No servers available for [%s]", $this->getEntity()), 404);
    }

    if (is_array($method)) {
      $data = $method;
      $method = 'POST';
    }

    $server = rtrim($this->_servers[0], '/');
    
    $data['nonce'] = $this->getNewNonce();
    $data['ts'] = time();
    
    if ($url = parse_url($path)) {
      if ($this->_method !== 'GET') {
        if (!empty($path['query'])) {
          parse_str($path['query'], $query);
          $data = array_merge($data, $query);
        }
        $path = $url['path'];
      }
    }

    $url = $server.'/'.ltrim($path, '/');

    $key = $this->get('mac_key');
    if (!$key) {
      $key = $this->getAppKey();
    }

    $mac = hash_hmac('sha256', self::base64UrlEncode(json_encode($data)), $key);

    $auth = sprintf('Authorization: MAC id="%s", ts="%s", nonce="%s", mac="%s"', $this->getClientId(), $data['ts'], $data['nonce'], $mac);

    return $this->_http->request($url, array(
      'method' => strtoupper($method),
      'body' => $data,
      'headers' => array(
        $auth,
        'Content-Type: application/vnd.tent.v0+json'
      )
    )); 
  }

  /**
   * Perform discovery on the given Profile entity
   * @return array Server URLs
   */
  function discover() {
    if (is_null($this->_discovery)) {
      $this->_discovery = $this->_http->request($this->getEntity(), array('method' => 'HEAD'));
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
        $profile = $this->_http->request($link);
        if (!$profile->isError()) {
          $this->_servers = array_merge($this->_servers, $profile->{self::$PROFILE_INFO_TYPES['core']}->servers);      
        }
      }
    }

    return $this->_servers;
  }

  /**
   * Get app registration; register if not registered yet.
   * On successful registration, this App's configuration will
   * be updated to reflect the new registration (ID, keys, etc.).
   * All configurable options will draw defaults from the config
   * passed to this App's constructor.
   * @param $options['redirect_uris'] (optional) Array of callback URLs this app will use, to satisfy oAuth workflow
   * @param $options['scopes'] (optional) Array of all the scopes this app will use
   * @see http://tent.io/docs/app-auth
   */ 
  function register($options = array()) {
    // already registered?
    if ($this->getAppId()) {
      return TentIO_Response::create(json_encode($this->getConfig()), 200);
    }

    if (!$servers = $this->discover()) {
      return TentIO_Response::error(404, sprintf("No servers available for [%s]", $this->getEntity()));
    }

    $options = array_merge(array(
      'name' => $this->_cfg['name'],
      'description' => $this->_cfg['description'],
      'url' => $this->getCurrentUrl(),
      'icon' => rtrim($this->getCurrentUrl(), '/').'/icon.png',
      'scopes' => $this->_cfg['scopes'],
      'redirect_uris' => $this->_cfg['redirect_uris']
    ), $options);

    $available_servers = $servers;
    do {
      $server = array_shift($available_servers);
      $response = $this->_http->request($server.'/apps', array(
        'method' => 'POST',
        'body' => $options,
        'headers' => array(
          'Content-Type: application/vnd.tent.v0+json',
          'Accept: application/vnd.tent.v0+json'
        )
      ));
    } while ($response->isError() && $available_servers);

    if (!$response->isError()) {
      $this->_clientId = $this->_cfg['id'] = $response->id;
      $this->setAppId($this->_cfg['mac_key_id'] = $response->mac_key_id);
      $this->setAppKey($this->_cfg['mac_key'] = $response->mac_key);
      $this->_cfg['mac_algorithm'] = $response->mac_algorithm;
      $this->_servers = $this->_cfg['servers'] = $servers;
    }

    return $response;   
  }

  /**
   * Generate the URL to which the user should be redirected
   * to authenticate a Tent.io session. All configurable options 
   * will draw defaults from the config passed to this App's 
   * constructor.
   * @param $options['id'] (optional) The register Client ID of this App (not App ID, used for MAC signing)
   * @param $options['scope'] (optional) Comma-separated list of permissions this App requires
   * @param $options['redirect_uris'] (optional) Array of callback URLs this app will use, to satisfy oAuth workflow
   * @param $options['tent_profile_info_types'] (optional) Array of Profile Info Type URLs this App should have access to
   * @param $options['tent_post_types'] (optional) Array of Post Types this App should have access to
   * @return string The URL to which the active user session should be redirected to authenticate
   * @throws TentAppException When no servers can be discovered for the given Entity.
   * @throws TentAppException When this App is not registered and cannot be registered.
   */
  function getLoginUrl($options = array()) {
    $options = array_merge(array(
      'scope' => implode(',', array_keys($this->_cfg['scopes'])),
      'redirect_uri' => $this->_cfg['redirect_uris'][0],
      'tent_profile_info_types' => $this->_cfg['tent_profile_info_types'],
      'tent_post_types' => $this->_cfg['tent_post_types']
    ), $options);

    if (empty($options['client_id']) && !empty($options['client_id'])) {
      $options['client_id'] = $options['id'];
    }

    if (empty($options['client_id'])) {
      $response = $this->register();
      if ($response->isError()) {
        throw new TentIO_AppException("This app could not be registered.", $response);
      }
      $options['client_id'] = $this->getClientId();
    }

    $server = $this->_servers[0];
    if (!$server) {
      if (!$servers = $this->discover()) {
        throw new TentIO_AppException("Failed to discover servers for Entity [{$this->_entity}]");
      }
      $server = array_shift($servers);
    }
    
    $this->set('state', $options['state'] = $this->getNewNonce());

    return $server.'/oauth/authorize?'.http_build_query(array(
      'client_id' => $options['client_id'],
      'response_type' => 'code',
      'scope' => $options['scope'],
      'redirect_uri' => $options['redirect_uri'],
      'tent_profile_info_types' => $options['tent_profile_info_types'],
      'tent_post_types' => $options['tent_post_types'],
      'state' => $options['state']
    ));
  }

}