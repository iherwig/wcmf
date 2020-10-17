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
namespace wcmf\lib\presentation;

/**
 * ResponseDocument is the interface for media returned in a response when
 * using the DownloadFormat. Implementations are responsible for providing
 * the response body content and mime type information.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface ResponseDocument {

  /**
   * Get the MIME type of the document
   * @return String
   */
  public function getMimeType();

  /**
   * Determine whether the document should be return as download or not
   * @return Boolean
   */
  public function isDownload();

  /**
   * Get the filename used in the 'Content-Disposition' header when used as a download
   * @return String
   */
  public function getFilename();

  /**
   * Get the cache date of the document, if it is cached locally
   * @return DateTime or null
   */
  public function getCacheDate();

  /**
   * Get the content of the document
   * NOTE The result might be null depending on the implementation
   * @return String
   */
  public function getContent();

  /**
   * Output the document using echo
   */
  public function output();
}
?>
