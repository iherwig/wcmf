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

/**
 * MemoryDocument represents content that resides in memory.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class MemoryDocument extends AbstractDocument {

  private $content = '';

  /**
   * Constructor
   * @param $content
   * @param $mimeType
   * @param $isDownload
   * @param $filename
   */
  public function __construct($content, $mimeType, $isDownload, $filename) {
    $this->content = $content;
    parent::__construct($mimeType, $isDownload, $filename);
  }

  /**
   * @see ResponseDocument::getContent()
   */
  public function getContent() {
    return $this->content;
  }

  /**
   * @see ResponseDocument::output()
   */
  public function output() {
    echo $this->content;
  }
}
?>
