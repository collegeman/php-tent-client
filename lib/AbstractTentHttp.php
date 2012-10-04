<?php
/**
 * Models an HTTP client. 
 * @license MIT
 */
abstract class AbstractTentHttp {

  /**
   * Build and send an HTTP request.
   * @param string $options['method'] Should be the HTTP method; should default to GET
   * @param mixed $options['body'] Should be the request body
   * @param mixed $options['file'] An iTentFile object, when sending a file
   * @param mixed $options['headers'] An array of headers to send, formatted "Name: Value"
   * @return TentResponse
   */
  abstract function request($url, $options = array());

}