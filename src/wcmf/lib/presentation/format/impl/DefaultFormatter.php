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
namespace wcmf\lib\presentation\format\impl;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\presentation\format\Formatter;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;

/**
 * Default Formatter implementation.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultFormatter implements Formatter {

  private $formats = [];

  /**
   * Constructor
   * @param $formats Array of Format instances to use
   */
  public function __construct(array $formats) {
    $this->formats = $formats;
  }

  /**
   * @see Formatter::getFormat()
   */
  public function getFormat($name) {
    if (isset($this->formats[$name])) {
      return $this->formats[$name];
    }
    throw new ConfigurationException("Configuration section 'Formats' does not contain a format definition with name: ".$name);
  }

  /**
   * @see Formatter::getFormatFromMimeType()
   */
  public function getFormatFromMimeType($mimeType) {
    $firstFormat = null;
    foreach ($this->formats as $name => $instance) {
      $firstFormat = $firstFormat == null ? $name : $firstFormat;
      $formatMimeType = $instance->getMimeType();
      if (strlen($formatMimeType) > 0 && strpos($mimeType, $formatMimeType) !== false) {
        return $name;
      }
    }
    if ($firstFormat == null) {
      throw new ConfigurationException("Configuration section 'Formats' does not contain a format definition for: ".$mimeType);
    }
    return $firstFormat;
  }

  /**
   * @see Formatter::deserialize()
   */
  public function deserialize(Request $request) {
    // get the format that should be used for this request format
    $formatName = $request->getFormat();
    if (strlen($formatName) == 0) {
      // the format must be given!
      throw new ConfigurationException("No request format defined for ".$request->__toString());
    }
    $format = $this->getFormat($formatName);
    $format->deserialize($request);
  }

  /**
   * @see Formatter::serialize()
   */
  public function serialize(Response $response) {
    // handle caching
    $responseSent = false;
    $cacheId = $response->getCacheId();
    if ($cacheId != null) {
      // get the caching request headers
      $request = $response->getRequest();
      $ifModifiedSinceHeader = $request->hasHeader("If-Modified-Since") ?
              $request->getHeader("If-Modified-Since") : false;
      $etagHeader = $request->hasHeader("If-None-Match") ?
              trim($request->getHeader("If-None-Match")) : false;

      // get caching parameters from response
      $cacheDate = $response->getCacheDate();
      $lastModified =  $cacheDate !== null ? $cacheDate : new \DateTime();
      $lastModifiedTs = $lastModified->getTimestamp();
      $etag = hash('sha256', $lastModifiedTs.$cacheId);
      $maxAge = $response->getCacheLifetime() !== null ? $response->getCacheLifetime() : 31536000;

      // send caching headers
      if (!$response->hasHeader("Cache-Control")) {
        $response->setHeader("Cache-Control", "public, max-age=".$maxAge);
      }
      $response->setHeader("Last-Modified", $lastModified->format("D, d M Y H:i:s")." GMT");
      $response->setHeader("ETag", $etag);

      // check if page has changed and send 304 if not
      if (($ifModifiedSinceHeader !== false && strtotime($ifModifiedSinceHeader) == $lastModifiedTs) ||
              $etagHeader == $etag) {
        // client has current response already
        $response->setStatus(304);
        $responseSent = true;
      }
    }

    // remove unwanted headers
    $response->setHeader('X-Powered-By', null);
    $response->setHeader('Expires', null);

    // delegate serialization to the response format
    if (!$responseSent) {
      $formatName = $response->getFormat();
      if (strlen($formatName) == 0) {
        // the format must be given!
        throw new ConfigurationException("No response format defined for ".$response->__toString());
      }
      $format = $this->getFormat($formatName);

      // send headers
      if (!headers_sent()) {
        foreach ($format->getResponseHeaders($response) as $name => $value) {
          if (strlen($value) > 0) {
            header($name.": ".$value);
          }
          else {
            header_remove($name);
          }
        }
      }

      // send body
      $format->serialize($response);
    }

    http_response_code($response->getStatus());
  }
}
?>
