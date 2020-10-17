<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2020 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\presentation\impl;

use wcmf\lib\presentation\format\Formatter;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;
use wcmf\lib\presentation\ResponseDocument;

/**
 * Default Response implementation.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultResponse extends AbstractControllerMessage implements Response {

  private $formatter = null;
  private $request = null;
  private $cacheId = null;
  private $cacheLifetime = null;
  private $status = 200;
  private $document = null;

  /**
   * Constructor
   * @param $formatter
   */
  public function __construct(Formatter $formatter) {
    parent::__construct($formatter);
    $this->formatter = $formatter;
  }

  /**
   * @see Response::setRequest()
   */
  public function setRequest(Request $request) {
    $this->request = $request;
    if ($request->getResponse() !== $this) {
      $request->setResponse($this);
    }
  }

  /**
   * @see Response::getRequest()
   */
  public function getRequest() {
    return $this->request;
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
   * @see Response::setCacheLifetime()
   */
  public function setCacheLifetime($seconds) {
    $this->cacheLifetime = $seconds !== null ? intval($seconds) : null;
  }

  /**
   * @see Response::getCacheLifetime()
   */
  public function getCacheLifetime() {
    return $this->cacheLifetime;
  }

  /**
   * @see Response::isCached()
   */
  public function isCached() {
    $format = $this->formatter->getFormat($this->getFormat());
    return $this->getCacheId() != null && $format->isCached($this);
  }

  /**
   * @see Response::getCacheDate()
   */
  public function getCacheDate() {
    $format = $this->formatter->getFormat($this->getFormat());
    return $this->isCached() ? $format->getCacheDate($this) : null;
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
    $document = (strlen($content) > 0) ?
      new MemoryDocument($content, $type, $isDownload, $filename) :
      new FileDocument($filename, $isDownload);
    $this->setDocument($document);
  }

  /**
   * @see Response::getFile()
   */
  public function getFile() {
    if ($this->document) {
      return [
          'isDownload' => $this->document->isDownload(),
          'filename' => $this->document->getFilename(),
          'content' => $this->document->getContent(),
          'type' => $this->document->getMimeType(),
      ];
    }
    return null;
  }

  /**
   * @see Response::setDocument()
   */
  public function setDocument(ResponseDocument $document) {
    $this->document = $document;
    $this->setFormat('download');
  }

  /**
   * @see Response::getDocument()
   */
  public function getDocument() {
    return $this->document;
  }
}
?>
