<?php
class RemoteTentResponse extends TentResponse {

  private $_statusCode;
  function setStatusHeader($code) {
    $this->_statusCode = $code;
  }

  function getStatusHeader() {
    
  }

  function write() {
    foreach($this->_headers as $name => $value) {
      echo "{$name}: {$value}\n";
    }
    echo "\n";
  }

}