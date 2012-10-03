<?php
/*
console.php
A simple console application for browsing Tent.io servers.
Copyright 2012 Fat Panda, LLC

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

require('../lib/RemoteTentRequest.php');

$app = new TentApp('https://collegeman.tent.is', array(
  'name' => 'Console',
  'description' => 'A simple console application for browsing Tent.io servers',
  'id' => 'izh3wr',
  'mac_key_id' => 'a:4fa89900',
  'mac_key' => 'bc7d759c0571e907e746c0f9650b8553',
  'mac_algorithm' => 'hmac-sha-256',
  'servers' => array(
    'https://collegeman.tent.is/tent'
  )
));

// $url = htmlentities(print_r($app->register(), true));

$url = $app->getLoginUrl();

?>
<doctype html>
<html>
  <head>
    <title>Tent.io Client Console</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/console.css">
  </head>
  <body>
    <div class="container">
      <pre><?php echo $url; ?></pre>
    </div>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/console.js"></script>
  </body>
</html>