<?php
/**
 * This file is an example of how to write a callback
 * handler to implement the oAuth workflow for a Tent App.
 * @license MIT
 */

require('bootstrap.php');

if (empty($entity)) {
  // user hasn't configured the console yet
  header('Location: /index.php?error=1');
  exit(1);
}

if (empty($config)) {
  // user hasn't register the app yet
  header('Location: /index.php?error=2');
  exit(2);
}

$config = $_SESSION[$entity]['app'];

$app = new TentIO_App($entity, $config);

