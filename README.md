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

The Tent.io protocol uses oAuth 2 for authentication. If you don't 
know anything about oAuth, you should [familiarize yourself](http://en.wikipedia.org/wiki/OAuth)
with this authentication workflow.

The Tent.io protocol uses [HTTP MAC Access Authentication](http://tools.ietf.org/html/draft-ietf-oauth-v2-http-mac-01),
to sign app requests. It's less important that you learn about this
because our implementation of the Client handles request signing for you.

## Example Usage - Right out of the box

First you need to load the client library.

    require('php-tent-client/lib/TentApp.php');

All of the client's dependencies will be autoloaded. (The autoloader
is defined at the top of `TentApp.php`.)

Next, you need to initialize your `$app` object. 

First, you'll need to provide the client with a Tent.io Entity URI - 
Tent.io's *username*, eachone representing a unique user's server 
(or cluster of servers - a feature that is handled by our client). 

Next, you'll need to provide meta data that describes your App to
the server you'll be connecting to. This data will appear in your
App's users' Tent profiles.

Finally, you'll need to configure the client with `redirect_uris`
and `scopes`. These features explain to the user's Tent server what
your App will be doing with the user's data, and how the user will
authenticate (oAuth workflow style).

The resulting code will look something like this:

    $entity = 'https://collegeman.tent.is';
    $app = new TentApp($entity, array(
      'name' => 'FooApp',
      'description' => 'Does amazing foos with your data',
      'url' => 'http://example.com',
      'icon' => 'http://example.com/icon.png',
      'redirect_uris' => array( 
        'https://app.example.com/tent/callback'
      ),
      'scopes' => array(
        'write_profile': 'Uses an app profile section to describe foos',
        'read_followings': 'Calculates foos based on your followings'
      )
    ));

If left undefined, `redirect_uris` will be set to an `array` containing
only one URL: the current URL of the request. And `scopes`, if left
undefined, will include all of the scopes proposed by the Tent.io 
protocol.

The next step is to register your client with the user's server. 

    $response = $app->register();
    if (!$response->isError()) {
      $config = $app->getConfig();
    } else {
      // for debugging
      $response->getErrorCode(); // the HTTP status
      $response->getRawBody(); // the raw HTTP response
      $response->getHeaders(); // HTTP headers
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

Now that you have your `mac_key_id` (public) and `mac_key` (private), 
you'll want to store these values somewhere and relate them back to the user's
Entity ID. For example, our console application stores its keys this way:

    $response = $app->register()
    if (!$response->isError()) {
      session_start();
      $_SESSION[$entity]['app'] = $app->getConfig();
    }

Our console app uses the PHP session to store these values - your app
will probably want to store them someplace a little more permanent,
like in a database.

Once stored, you can intialize your request object in future requests
using the stored configuration values, thus allowing you to skip the registration 
process.

    session_start();
    $entity = "https://collegeman.tent.is";
    $request = new TentApp($entity, $_SESSION[$entity]['app']);

The next step is to have the user log in, authorizing your 
app. Authentication begins by sending the user to his Tent.io 
server to login:

    $url = $app->getLoginUrl();
    header('Location: $url');


## Exploring with the Console

## Creating Your own Client

## Scopes, Profile Info Types, and Post Types

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