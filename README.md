php-tent-client
===============

Alpha Version 0.1

A client implementation of the [Tent.io](http://tent.io/) protocol, 
with a test console for exploring server contents.

For a server implementation in PHP, check out [php-tent-server](http://github.com/collegeman/php-tent-server).

If you'd like to be a contributor, checkout the [TODO](https://github.com/collegeman/php-tent-server/blob/master/TODO.md), 
then [e-mail us](mailto:yo@fatpandadev.com) to find out what we need
help with. Thanks!

## Getting Started

Understanding how to use this client will be easier if you understand
a little about what [Tent.io](http://tent.io/) is. You don't need to know how it works - 
our client satisifies the protocol for you - but having a general understanding of
the decentralized nature of the architecture would be a good place to start.

Also, the Tent.io protocol uses oAuth 2 for authentication. If you don't 
know anything about oAuth, you should [familiarize yourself](http://en.wikipedia.org/wiki/OAuth)
with this authentication workflow.

## Using this Client

First you need to load the client library.

    require('php-tent-client/lib/TentApp.php');

All of the client's other dependencies will be autoloaded.

Next, you need to initialize your `$app` object. To do this you need to 
provide the client with a Tent.io Entity URI - Tent.io's *username*, each
one representing a unique user's server:

    $app = new TentApp("https://collegeman.tent.is");

The next step is to register your client with the user's server. 

    $registration = $app->register();
    if (!$registration->isError()) {
      $config = $registration->body;
    } else {
      // for debugging
      $registration->getErrorCode(); // the HTTP status
      $registration->getRawBody(); // the raw HTTP response
      $registration->getHeaders(); // HTTP headers
    }

The contents of `$config` will look like this:

    Array
    (
      [name] => php-tent-client
      [description] => The PHP client library for Tent.io
      [tent_profile_info_types] => all
      [tent_post_types] => all
      [id] => ibor21
      [mac_key_id] => a:ffb757da
      [mac_key] => ab4634e2cb354c6ed334af09d6fb563f
      [mac_algorithm] => hmac-sha-256
      [servers] => array(
        'https://collegeman.tent.is/tent'
      )
    )

Now that you have your `mac_key_id` and `mac_key`, you'll want to
store these values somewhere and relate them back to the user's
Entity ID. For example, our console application stores its keys this way:

    if ($registration = $app->register()) {
      session_start();
      $_SESSION[$entity]['console_app'] = $registration->body;
    }

Once stored, you can intialize your request object with the stored
values, thus saving you the registration step:

    session_start();
    $entity = "https://collegeman.tent.is";
    $request = new TentApp($entity, $_SESSION[$entity]['console_app']);

Our console app uses the PHP session to store these values - your app
will probably want to store them someplace a little more permanent,
like in a database.

The next step is to facilitate authentication. Authentication begins
by sending the user to the Tent.io server to login:

    $url = $app->getLoginUrl();



## Exploring with the Console

## Licensing

### MIT License

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