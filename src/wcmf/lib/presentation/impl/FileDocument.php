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

use wcmf\lib\io\FileUtil;
use wcmf\lib\io\IOException;

/**
 * FileDocument represents a local file.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class FileDocument extends AbstractDocument {

  private $content = null;

  /**
   * Constructor
   * @param $file
   * @param $isDownload
   */
  public function __construct($filename, $isDownload) {
    if (!file_exists($filename)) {
      throw new IOException("The file '$filename' does not exist.");
    }
    $mimeType = FileUtil::getMimeType($filename);
    $this->content = file_get_contents($filename);
    parent::__construct($mimeType, $isDownload, $filename);
  }

  /**
   * @see ResponseDocument::getCacheDate()
   */
  public function getCacheDate() {
    return \DateTime::createFromFormat('U', filemtime($this->getFilename()));
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
    // make sure that no unwanted content is returned
    ob_end_clean();
    echo $this->content;
  }
}
?>
