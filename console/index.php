<?php
/*
index.php
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

require('bootstrap.php');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Tent Console</title>
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/prettify.css">
    <link rel="stylesheet" href="css/console.css">
  </head>
  <body>
    <form class="container">

      <?php if (empty($entity)) { ?>
        <div class="alert">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          Please login to use the console.
        </div>
      <?php } ?>

      <div class="alert alert-error" <?php if (empty($error)) echo 'style="display:none;"' ?>>
        <button type="button" class="close" onclick="$(this).parent().hide(); return false;">&times;</button>
        <?php echo $error ?>
      </div>
    
      <div class="row" style="padding-bottom: 8px;">
        <div class="span12 form-inline">
          <div class="input-append">
            <input tabindex="1" id="entity" type="text" class="span7" placeholder="https://username.test.is" value="<?php echo htmlentities($entity) ?>" <?php if (!empty($config['mac_key_id'])) echo 'readonly="readonly"' ?>>
            <label for="entity" class="label label-important" style="margin-right: 10px;">Entity URI</label> 
            <?php if (empty($config['mac_key_id'])) { ?>
              <button tabindex="2" type="button" class="btn" data-action="login" <?php if (empty($entity)) echo 'disabled="disabled"' ?>><i class="icon-user"></i> Login</button>
            <?php } else { ?>
              <button tabindex="3" class="btn" data-action="logout"><i class="icon-remove-circle"></i> Logout</button>
            <?php } ?>
          </div>
        </div>
      </div>
      <div style="position:relative;">
        <div class="row" style="padding-bottom:8px;">
          <div class="span10 form-inline">
            <div class="btn-group input-append input-prepend">
              <a tabindex="4" style="width:50px;" class="btn dropdown-toggle" data-toggle="dropdown" href="#">
                <span data-label-for="req-method">GET</span>&nbsp;<span class="caret"></span>
              </a>
              <ul class="dropdown-menu">
                <li><a href="javascript:;" data-action="set-req-method" data-req-method="GET"><i class="icon-ok"></i> GET</a></li>
                <li><a href="javascript:;" data-action="set-req-method" data-req-method="POST"><i class="icon-ok" style="visibility:hidden;"></i> POST</a></li>
                <li><a href="javascript:;" data-action="set-req-method" data-req-method="PUT"><i class="icon-ok" style="visibility:hidden;"></i> PUT</a></li>
                <li><a href="javascript:;" data-action="set-req-method" data-req-method="HEAD"><i class="icon-ok" style="visibility:hidden;"></i> HEAD</a></li>
              </ul>
              <input tabindex="5" id="path" type="text" class="span9" placeholder="" value="profile">
              <button tabindex="6" type="button" class="btn" data-action="add-field" disabled="disabled"><i class="icon-list"></i></button>
            </div>
            <a href="#" data-toggle="server" class="server"><i class="icon-arrow-right"></i> <span>https://username.tent.is/tent</span>/</a>
          </div>
          <div class="span2">
            <button tabindex="7" type="submit" class="btn btn-primary pull-right" data-action="submit" disabled="disabled">Submit <i class="icon-arrow-right icon-white"></i></button>
          </div>
        </div>
        <div id="fields" class="row"></div>
        <div id="field-template" style="display:none;">
          <div class="span12 form-inline" style="padding-bottom:8px;">
            <input type="text" class="span3" name="" placeholder="name">
            <input type="text" class="span7" name="" placeholder="value">
            <a href="#" tabindex="-1" data-action="remove-field" style="margin-left:5px;"><i class="icon-remove"></i></a>
          </div>
        </div>
        <pre id="output" class="prettyprint linenums">// no data</pre>
        <div class="onion" <?php if (!empty($entity)) echo 'style="display:none;"' ?>></div>
      </div>
    </form>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/prettify.js"></script>
    <script>
      var config = {
        'redirect_uri': '<?php 
          $currentUrl = TentIO_App::getCurrentUrl();
          if (strpos($currentUrl, 'index.php')) {
            $redirect_uri = str_replace('index.php', 'callback.php', $currentUrl);
          } else {
            $redirect_uri = rtrim($currentUrl, '/').'/callback.php';
          }
          echo $redirect_uri;
        ?>'
      };
    </script>
    <script src="js/console.js"></script>
  </body>
</html>