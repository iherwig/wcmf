<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2016 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\presentation\impl;

use wcmf\lib\io\FileUtil;
use wcmf\lib\presentation\format\Formatter;
use wcmf\lib\presentation\impl\AbstractControllerMessage;
use wcmf\lib\presentation\Response;

/**
 * Default Response implementation.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultResponse extends AbstractControllerMessage implements Response {

  private $cacheId = null;
  private $status = 200;
  private $file = null;
  private $isFinal = false;

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
    $this->cacheId = $cacheId;
  }

  /**
   * @see Response::getCacheId()
   */
  public function getCacheId() {
    return $this->cacheId;
  }

  /**
   * @see Response::setStatus()
   */
  public function setStatus($status) {
    $this->status = $status;
  }

  /**
   * @see Response::getStatus()
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * @see Response::setFile()
   */
  public function setFile($filename, $isDownload, $content='', $type='') {
    if (strlen($content) == 0 && file_exists($filename)) {
      $content = file_get_contents($filename);
      $type = FileUtil::getMimeType($filename);
    }
    $this->file = array(
        'isDownload' => $isDownload,
        'filename' => $filename,
        'content' => $content,
        'type' => $type
    );
    $this->setFinal();
  }

  /**
   * @see Response::getFile()
   */
  public function getFile() {
    return $this->file;
  }

  /**
   * @see Response::setFinal()
   */
  public function setFinal() {
    $this->isFinal = true;
  }

  /**
   * @see Response::isFinal()
   */
  public function isFinal() {
    return $this->isFinal;
  }
}
?>
