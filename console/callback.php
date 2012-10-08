<?php
/**
 * This file is an example of how to write a callback
 * handler to implement the oAuth workflow for a Tent App.
 * @license MIT
 */

require('bootstrap.php');

if (empty($entity)) {
  // user hasn't configured the console yet
  header('Location: /index.php?error='.CONSOLE_APP_LOGIN_ERROR);
  exit(1);
}

if (empty($config)) {
  // user hasn't register the app yet
  header('Location: /index.php?error='.CONSOLE_APP_REG_ERROR);
  exit(2);
}

$config = $_SESSION[$entity]['app'];

$app = new TentIO_App($entity, $config);

try {
  if ($access_token = $app->getUserAccessToken()) {
    header('Location: /');
  } else {
    header('Location: /index.php?error='.CONSOLE_APP_AUTH_ERROR); 
  }
} catch (Exception $e) {
  header('Location: /index.php?error='.CONSOLE_APP_AUTH_ERROR); 
}