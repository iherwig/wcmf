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

use wcmf\lib\presentation\ResponseDocument;

/**
 * AbstractFormat is used as base class for specialized documents.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class AbstractDocument implements ResponseDocument {

  private $mimeType = 'application/octet-stream';
  private $isDownload = false;
  private $filename = '';

  /**
   * Constructor
   * @param $mimeType
   * @param $isDownload
   * @param $filename
   */
  public function __construct($mimeType, $isDownload, $filename) {
    $this->mimeType = $mimeType;
    $this->isDownload = $isDownload;
    $this->filename = $filename;
  }

  /**
   * @see ResponseDocument::getMimeType()
   */
  public function getMimeType() {
    return $this->mimeType;
  }

  /**
   * @see ResponseDocument::isDownload()
   */
  public function isDownload() {
    return $this->isDownload;
  }

  /**
   * @see ResponseDocument::getFilename()
   */
  public function getFilename() {
    return $this->filename;
  }

  /**
   * @see ResponseDocument::getCacheDate()
   */
  public function getCacheDate() {
    return null;
  }
}
?>
