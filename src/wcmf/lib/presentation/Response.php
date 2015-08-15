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
namespace wcmf\lib\presentation;

/**
 * Response holds the response values that are used as output from
 * Controller instances. It is typically instantiated by the ActionMapper
 * instance and filled during Controller execution.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface Response extends ControllerMessage {

  const STATUS_200 = '200 OK';
  const STATUS_201 = '201 Created';
  const STATUS_202 = '202 Accepted';
  const STATUS_204 = '204 No Content';

  const STATUS_400 = '400 Bad Request';
  const STATUS_404 = '404 Not Found';

  /**
   * Set a string value that uniquely identifies the request data
   * that cause the current response. This value maybe used to compare
   * two requests and return cached responses based on the result.
   * @param $cacheId
   */
  public function setCacheId($cacheId);

  /**
   * Get the cache id.
   * @see Response::setCacheId()
   * @return The id
   */
  public function getCacheId();

  /**
   * Set the response HTTP status code
   * @param $status The HTTP status code
   */
  public function setStatus($status);

  /**
   * Get the response HTTP status code
   * @return String
   */
  public function getStatus();

  /**
   * Set a file download.
   * NOTE: This automatically sets the response to final (see Response::setFinal)
   * @param $filename The name of the file, must be a real file, if no content is provided
   * @param $content File content, if in-memory only (optional)
   */
  public function setFile($filename, $content='');

  /**
   * Get the file download
   * @return Array with keys 'filename' and 'content' or null
   */
  public function getFile();

  /**
   * Make sure there is no further processing after this response
   */
  public function setFinal();

  /**
   * Check if the response forbids further processing or not
   * @return Boolean
   */
  public function isFinal();
}
?>
