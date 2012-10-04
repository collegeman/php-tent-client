<?php
/**
 * Boostrap for our Console app.
 * @license MIT
 */
require('../lib/TentApp.php');

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
}

$error = false;
if (isset($_REQUEST['error'])) {
  switch($_REQUEST['error']) {
    case 1: 
      $error = '<b>Oops!</b> Please login to use the console.';
      break;
    case 2:
      $error = "<b>Oops!</b> Your app isn't registered yet. Please check configuration, or try again.";
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

  if (!$entity = filter_var(trim($_POST['entity']), FILTER_VALIDATE_URL)) {
    throw new Exception("That Entity URI doesn't look like a URI should.");
  }

  if (!empty($_POST['redirect_uri'])) {
    $config['redirect_uris'] = array($_POST['redirect_uri']);
  }

  $app = new TentApp($entity, $config);
  $result = array(
    'url' => $app->getLoginUrl()
  );
  // stash the registration for subsequent requests
  $_SESSION['entity'] = $entity;
  $_SESSION[$entity]['app'] = $app->getConfig();
  return $result;
}

function api_post_logout() {
  session_destroy();
}