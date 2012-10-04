<?php
/**
 * Boostrap for our Console app.
 * @license MIT
 */

require('bootstrap.php');

$method = getRequestMethod();
$action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : 'index';
$function = "api_{$method}_{$action}";

header('Content-Type: application/json');
header('Cache-Control: max-age=0, private, must-revalidate');

if (strpos($function, 'api_') !== 0) {
  die('Invalid request');
}

if (!is_callable($function)) {
  die('Invalid request');
}

try {
  $result = call_user_func_array($function, $_REQUEST);
  if ($result instanceof TentResponse) {
    $result->write();
  } else {
    echo json_encode($result);
  }
} catch (Exception $e) {
  $error = $e->getMessage();
  header("HTTP/1.1 500 Internal Server Error");
  echo json_encode(array('error' => $error));
}