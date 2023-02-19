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
 * RemoteDocument represents a remote file retrieved via cURL.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class RemoteDocument extends AbstractDocument {

  private $curlOptions = [];

  /**
   * Constructor
   * @param $curlOptions
   * @param $isDownload
   */
  public function __construct($curlOptions, $isDownload) {
    if (!function_exists('curl_init')) {
      throw new IOException("cURL is required to use RemoteDocument.");
    }
    if (!isset($curlOptions[CURLOPT_URL])) {
      throw new IOException("No url set in cURL options.");
    }
    $this->curlOptions = $curlOptions;

    // get filename from url
    $filename = basename(parse_url($curlOptions[CURLOPT_URL], PHP_URL_PATH));
    $mimeType = FileUtil::getMimeType($filename);
    parent::__construct($mimeType, $isDownload, $filename);
  }

  /**
   * @see ResponseDocument::getContent()
   */
  public function getContent() {
    return null;
  }

  /**
   * @see ResponseDocument::output()
   */
  public function output() {
    $ch = curl_init($this->curlOptions[CURLOPT_URL]);
    foreach ($this->curlOptions as $key => $value) {
      curl_setopt($ch, $key, $value);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_WRITEFUNCTION, [$this, 'write']);
    curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if ($error) {
      throw new IOException("Error retrieving remote document: ".$error);
    }
  }

  private function write($ch, $data) {
    echo $data;
    return strlen($data);
  }
}
?>
