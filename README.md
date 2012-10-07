# php-tent-client (v.0.1a)

A client implementation of the [Tent.io](http://tent.io/) protocol, 
with a test console for exploring server contents.

For a server implementation in PHP, check out [php-tent-server](http://github.com/collegeman/php-tent-server).

If you'd like to be a contributor, checkout the [TODO](https://github.com/collegeman/php-tent-server/blob/master/TODO.md), 
then [e-mail us](mailto:yo@fatpandadev.com) to find out what we need
help with. Thanks!

## Requirements

* PHP 5.2.4 or later
* cURL extension, or write your own implementation of `TentIO_AbstractHttp`

## Installation

If you're using a framework that satisfies [PSR-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md), then
all you need to do is add `TentIO/**` to your project's `lib` folder.

If you're not using any of these frameworks, a simple autoloader will suffice:

    function tentio_autoload($className) {
      $className = ltrim($className, '\\');
      $fileName  = '';
      $namespace = '';
      if ($lastNsPos = strripos($className, '\\')) {
        $namespace = substr($className, 0, $lastNsPos);
        $className = substr($className, $lastNsPos + 1);
        $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
      }
      $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

      require 'path/to/php-tent-client/lib/'.$fileName;
    }

    spl_autoload_register('tentio_autoload');

Support for composer is coming soon.

## Usage

First, you'll need to initialize your `$app` object using the
Entity URI of the user to whom you wish to connect.

You'll also need to provide meta data that describes your App to
the server you'll be connecting to - `name`, `description`,
`url`, and `icon` provide information that appears in your
App's users' Tent profiles.

Finally, you'll need to configure the client with `redirect_uris`
and `scopes`. These features explain to the user's Tent server what
your App will be doing with the user's data, and how the user will
authenticate (oAuth workflow style).

The resulting code will look something like this:

    $entity = 'https://collegeman.tent.is';

    $app = new TentIO_App($entity, array(
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
only one URL: the URL of the current request. And `scopes`, if left
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

You'll want to store this data somewhere and relate it back to the user's
Entity URI. For example, our console application stores its keys this way:

    $response = $app->register()
    if (!$response->isError()) {
      session_start();
      $_SESSION[$entity]['app'] = $app->getConfig();
    }

Our console app uses the PHP session to store these values - your app
will probably want to store them someplace a little more permanent,
like in a database.

Once stored, you can intialize your request object in future requests
using the stored configuration, skipping registration.

    session_start();
    $entity = "https://collegeman.tent.is";
    $request = new TentIO_App($entity, $_SESSION[$entity]['app']);

The next step is to have the user log in, authorizing your 
app. Authentication begins by sending the user to his Tent.io 
server to login:

    $url = $app->getLoginUrl();
    header('Location: $url');


## Exploring With the Console

## Creating Your Own App

### Scopes, Profile Info Types, and Post Types

## Credits

Thanks to everyone who supported the development of this client.

(beberlei)[https://github.com/beberlei] - For bringing to our attention the [PHP-FIG](http://www.php-fig.org/), [PSR-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md), and [Composer](http://getcomposer.org/)

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