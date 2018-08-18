<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2018 wemove digital solutions GmbH
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

  /**
   * Set the Request instance belonging to the response and vice versa.
   * @param $request Request
   */
  public function setRequest(Request $request);

  /**
   * Get the Request instance belonging to the response.
   * @return Request
   */
  public function getRequest();

  /**
   * Set a string value that uniquely identifies the request data
   * resulting in the current response. If this value is not null,
   * it will be used to compare two requests and return cached responses
   * based on the result. Set a value of null to prevent caching.
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
   * Set the lifetime of a cached response. After this time
   * previously cached response is dicarded. Set a value of -1 for
   * an infinite lifetime.
   * @param $seconds
   */
  public function setCacheLifetime($seconds);

  /**
   * Get the lifetime of a cached response. A value of null means
   * an infinite lifetime.
   * @return Integer
   */
  public function getCacheLifetime();

  /**
   * Check if the response is cached. Controllers may use the result
   * to determine if the controller logic must be executed or not.
   * @return Boolean
   */
  public function isCached();

  /**
   * Get the caching date, if the response is cached.
   * @return DateTime or null, if not cached
   */
  public function getCacheDate();

  /**
   * Set the response HTTP status code
   * @param $status The HTTP status code
   */
  public function setStatus($status);

  /**
   * Get the response HTTP status code
   * @return Integer
   */
  public function getStatus();

  /**
   * Set a file as response.
   * @param $filename The name of the file, must be a real file, if no content is provided
   * @param $isDownload Boolean, indicating whether the file should be return as download or not
   * @param $content File content, if in-memory only (optional)
   * @param $type File mime type, if in-memory only (optional)
   */
  public function setFile($filename, $isDownload, $content='', $type='');

  /**
   * Get the file download
   * @return Array with keys 'isDownload', 'filename', 'content' and 'type' or null
   */
  public function getFile();
}
?>
