php-tent-client
===============

A client implementation of Tent.io protocol, with a test console for exploring server contents.

For a server implementation in PHP, check out [php-tent-server](http://github.com/collegeman/php-tent-server).

If you'd like to be a contributor, checkout the [TODO](https://github.com/collegeman/php-tent-server/blob/master/TODO.md), 
then [e-mail us](mailto:yo@fatpandadev.com) to find out what we need help with. Thanks!

## Quick Start

    // load the client - all other dependencies will be autoloaded
    require('php-tent-client/lib/RemoteTentRequest.php');

    // $entity is a Tent.io Entity URI, e.g., "https://collegeman.tent.is"
    $request = new RemoteTentRequest($entity);

    // make the request
    $response = $request->api('/profile');
    if (!$response->isError()) {
      print_r($response->body);
    } else {
      echo $response->getErrorMessage();
    }

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