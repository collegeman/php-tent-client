<?php
/**
 * Models a File being sent to or from a Tent Server.
 */
interface TentIO_iFile {

  /**
   * @return string Mime-type of the file
   */
  function getMimeType();

  /**
   * @return mixed Text or binary content of the file
   */
  function getContents();

  /** 
   * @return int bytes
   */
  function getSize();

}