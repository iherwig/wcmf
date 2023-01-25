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
 * Response holds the response values that are used as output from
 * Controller instances. It is typically instantiated by the ActionMapper
 * instance and filled during Controller execution.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface Response extends ControllerMessage {

  /**
   * Set the Request instance belonging to the response and vice versa.
   * @param Request $request
   */
  public function setRequest(Request $request): void;

  /**
   * Get the Request instance belonging to the response.
   * @return Request
   */
  public function getRequest(): Request;

  /**
   * Set a string value that uniquely identifies the request data
   * resulting in the current response. If this value is not null,
   * it will be used to compare two requests and return cached responses
   * based on the result. Set a value of null to prevent caching.
   * @param string $cacheId
   */
  public function setCacheId(string $cacheId): void;

  /**
   * Get the cache id.
   * @see Response::setCacheId()
   * @return ?string
   */
  public function getCacheId(): ?string;

  /**
   * Set the lifetime of a cached response. After this time
   * previously cached response is dicarded. Set a value of -1 for
   * an infinite lifetime.
   * @param int $seconds
   */
  public function setCacheLifetime(int $seconds): void;

  /**
   * Get the lifetime of a cached response. A value of null means
   * an infinite lifetime.
   * @return int
   */
  public function getCacheLifetime(): int;

  /**
   * Check if the response is cached. Controllers may use the result
   * to determine if the controller logic must be executed or not.
   * @return bool
   */
  public function isCached(): bool;

  /**
   * Get the caching date, if the response is cached.
   * @return \DateTime or null, if not cached
   */
  public function getCacheDate(): ?\DateTime;

  /**
   * Set the response HTTP status code
   * @param int $status The HTTP status code
   */
  public function setStatus(int $status): void;

  /**
   * Get the response HTTP status code
   * @return int
   */
  public function getStatus(): int;

  /**
   * Set a file as response
   * @deprecated Use setDocument() instead
   * @param string $filename The name of the file, must be a real file, if no content is provided
   * @param bool $isDownload Boolean, indicating whether the file should be return as download or not
   * @param string $content File content, if in-memory only (optional)
   * @param string $type File mime type, if in-memory only (optional)
   */
  public function setFile(string $filename, bool $isDownload, string $content='', string $type=''): void;

  /**
   * Get the file download
   * @deprecated Use getDocument() instead
   * @return array{'isDownload': bool, 'filename': string, 'content': string, 'type': string}
   */
  public function getFile(): array|null;

  /**
   * Set a response document
   * @param ResponseDocument $document
   */
  public function setDocument(ResponseDocument $document): void;

  /**
   * Get the response document
   * @return ResponseDocument
   */
  public function getDocument(): ResponseDocument;
}
?>
