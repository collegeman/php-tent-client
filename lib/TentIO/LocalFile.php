<?php
/**
 * @see iTentFile
 */
class TentIO_LocalFile implements TentIO_iFile {

  private $_path;
  function __construct($path, $mimeType = null, $contents = null) {
    $this->_path = $path;
    $this->_mimeType = $mimeType;
    $this->_contents = $contents;
  }

  private $_mimeType;
  function getMimeType() {
    if (is_null($this->_mimeType)) {
      $finfo->file($file);
      $this->_mimeType = finfo_open(FILEINFO_MIME);
      finfo_close($finfo);
    }
    return $this->_mimeType;
  }
    
  function getContents() {
    if (is_null($this->_contents)) {
      $this->_contents = file_get_contents($this->_path);
    }
    return $this->_contents;
  }

  private $_size;
  function getSize() {
    if (is_null($this->_size)) {
      $this->_size = filesize($this->_path);
    }
    return $this->_size;
  }

  function __toString() {
    return "{$this->_path}; mimeType='".$this->getMimeType()."'; size=".$this->getSize();
  }
      
}