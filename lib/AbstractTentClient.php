<?php
/**
 * @license MIT
 */
abstract class AbstractTentClient {

  function __construct() {}

  abstract function request($url, $options = array());

  /**
   * @return AbstractTentResponse
   */
  function send(/* AbstractTentRequest */ $request) {
    return TentResponse::error("Not yet implemented.");
  }

}