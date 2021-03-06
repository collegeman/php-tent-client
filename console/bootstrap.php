<?php
/**
 * Boostrap for our Console app.
 * @license MIT
 */

//https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md
function console_autoload($className) {
  $className = ltrim($className, '\\');
  $fileName  = '';
  $namespace = '';
  if ($lastNsPos = strripos($className, '\\')) {
    $namespace = substr($className, 0, $lastNsPos);
    $className = substr($className, $lastNsPos + 1);
    $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
  }
  $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

  require '../lib/'.$fileName;
}

spl_autoload_register('console_autoload');

session_start();

if (!empty($_SESSION['entity'])) {
  $entity = $_SESSION['entity'];
}

if (!empty($_SESSION[$entity]['app'])) {
  $config = $_SESSION[$entity]['app'];
}

if (empty($config)) {
  $config = array(
    'name' => 'TentConsole',
    'description' => 'An application for browsing Tent.io servers'
  );
} else {
  $app = new TentIO_App($entity, $config);
  if ($app->register()) {
    try {
      // we use this to determine logged-in state
      $access_token = $app->getUserAccessToken();
    } catch (Exception $e) {
      // exceptions here could simply mean incomplete auth
      // so we just ignore them
    }
  }
}

define('CONSOLE_APP_LOGIN_ERROR', 1);
define('CONSOLE_APP_REG_ERROR', 2);
define('CONSOLE_APP_AUTH_ERROR', 4);

$error = false;
if (isset($_REQUEST['error'])) {
  switch($_REQUEST['error']) {
    case CONSOLE_APP_LOGIN_ERROR: 
      $error = '<b>Oops!</b> Please login to use the console.';
      break;
    case CONSOLE_APP_REG_ERROR:
      $error = "<b>Oops!</b> Your app isn't registered yet. Please check configuration, or try again.";
      break;
    case CONSOLE_APP_AUTH_ERROR:
      $error = "<b>Oops!</b> Failed to authenticate.";
      break;
  }
}

function getRequestMethod() {
  $headers = apache_request_headers();
  if (!empty($headers['X-HTTP-Method-Override'])) {
    return strtolower($headers['X-HTTP-Method-Override']);
  } else if (!empty($_REQUEST['_method'])) {
    return strtolower(trim($_REQUEST['_method']));
  } else {
    return strtolower($_SERVER['REQUEST_METHOD']);
  }
}

function api_post_login() {
  global $config;

  if (!$entity = TentIO_App::isValidUrl(trim($_POST['entity']))) {
    throw new Exception("That Entity URI doesn't look like a URI should.");
  }

  if (!empty($_POST['redirect_uri'])) {
    $config['redirect_uris'] = array($_POST['redirect_uri']);
  }

  $app = new TentIO_App($entity, $config);
  $result = array(
    'url' => $app->getLoginUrl(array(
      'tent_post_types' => array(
        'https://tent.io/types/post/status/v0.1.0',
        'https://tent.io/types/post/repost/v0.1.0'
      ),
      'tent_profile_info_types' => array(
        'https://tent.io/types/info/basic/v0.1.0'
      )
    ))
  );
  // stash the registration for subsequent requests
  $_SESSION['entity'] = $entity;
  $_SESSION[$entity]['app'] = $app->getConfig();
  return $result;
}

function api_post_request() {
  global $app;
  return $app->api($_POST['path'], $_POST['method'], $_POST['data']);
}

function api_post_logout() {
  global $app;

  // // try to clean up app
  // if ($app->register()) {
  //   try {
  //     $app->api('/apps/'.$app->getClientId(), 'DELETE');
  //   } catch (Exception $e) {}
  // }

  session_destroy();
}