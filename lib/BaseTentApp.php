<?php
/**
 * Models a TentApp. This is a thin wrapper around 
 * the RemoteTentRequest class, facilitating the storage
 * of user-specific tokens, e.g., user access token and
 * mac key, as well as the CSRF-defending state arg.
 * @license MIT
 */
abstract class BaseTentApp extends RemoteTentRequest {

  /**
   * Store a user token, indexed to the current
   * target Entity.
   * @param string The arg name
   * @param mixed The arg value
   * @see TentApp for a session-based example
   */
  abstract function set($name, $value = null);

  /**
   * Retrieve a user token.
   * @param string The arg name
   * @param mixed A default value; defaults to false
   * @see TentApp for a session-based example
   */
  abstract function get($name, $default = false);

  /**
   * Wipe-out any stored user tokens for the current
   * target Entity.
   * @see TentApp for a session-based example
   */
  abstract function destroySession();

  /**
   * Generate the URL to which the user should be redirected
   * to authenticate a Tent.io session.
   * @return string The URL
   * @throws Exception When this client is not registered.
   * @throws Exception When no servers can be found for the given Entity.
   */
  function getLoginUrl($config = array()) {
    $config = array_merge(array(
      'id' => $this->_cfg['id'],
      'scope' => implode(',', array_keys($this->_cfg['scopes'])),
      'redirect_uri' => $this->_cfg['redirect_uris'][0],
      'tent_profile_info_types' => $this->_cfg['tent_profile_info_types'],
      'tent_post_types' => $this->_cfg['tent_post_types']
    ), $config);

    if (empty($config['client_id'])) {
      $config['client_id'] = $config['id'];
    }
    unset($config['id']);
    
    // validate...

    $this->set("state_{$config['id']}", $config['state'] = md5($this->_entity.uniqid()));

    $server = $this->_servers[0];
    if (!$server) {
      if (!$servers = $this->discover()) {
        throw new Exception("Failed to discover servers for Entity [{$this->_entity}]");
      }
      $server = array_shift($servers);
    }

    return $server.'/oauth/authorize?'.http_build_query(array(
      'client_id' => $config['client_id'],
      'scope' => $config['scope'],
      'redirect_uri' => $config['redirect_uri'],
      'tent_profile_info_types' => $config['tent_profile_info_types'],
      'tent_post_types' => $config['tent_post_types']
    ));
  }

}