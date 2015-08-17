<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2015 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\presentation\impl;

use wcmf\lib\presentation\format\Formatter;
use wcmf\lib\presentation\impl\AbstractControllerMessage;
use wcmf\lib\presentation\Response;

/**
 * Default Response implementation.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultResponse extends AbstractControllerMessage implements Response {

  private $_cacheId = null;
  private $_status = self::STATUS_200;
  private $_file = null;
  private $_isFinal = false;

  /**
   * Constructor
   * @param $formatter
   */
  public function __construct(Formatter $formatter) {
    parent::__construct($formatter);
  }

  /**
   * @see Response::setCacheId()
   */
  public function setCacheId($cacheId) {
    $this->_cacheId = $cacheId;
  }

  /**
   * @see Response::getCacheId()
   */
  public function getCacheId() {
    return $this->_cacheId;
  }

  /**
   * @see Response::setStatus()
   */
  public function setStatus($status) {
    $this->_status = $status;
  }

  /**
   * @see Response::getStatus()
   */
  public function getStatus() {
    return $this->_status;
  }

  /**
   * @see Response::setFile()
   */
  public function setFile($filename, $content='') {
    if (strlen($content) == 0 && file_exists($filename)) {
      $content = file_get_contents($filename);
    }
    $this->_file = array(
        'filename' => $filename,
        'content' => $content
    );
    $this->setFinal();
  }

  /**
   * @see Response::getFile()
   */
  public function getFile() {
    return $this->_file;
  }

  /**
   * @see Response::setFinal()
   */
  public function setFinal() {
    $this->_isFinal = true;
  }

  /**
   * @see Response::isFinal()
   */
  public function isFinal() {
    return $this->_isFinal;
  }
}
?>
