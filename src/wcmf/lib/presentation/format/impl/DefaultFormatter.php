<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2017 wemove digital solutions GmbH
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

  private static $headersSent = false;

  private $formats = array();

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
      if (strpos($mimeType, $instance->getMimeType()) !== false) {
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
    self::$headersSent = headers_sent();

    // if the response has a file, we send it and return
    $file = $response->getFile();
    if ($file) {
      self::sendHeader("Content-Type: ".$file['type']);
      if ($file['isDownload']) {
        self::sendHeader('Content-Disposition: attachment; filename="'.basename($file['filename']).'"');
      }
      self::sendHeader("Pragma: no-cache");
      self::sendHeader("Expires: 0");
      echo $file['content'];
      return;
    }

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
      $etag = md5($lastModifiedTs.$cacheId);

      // send caching headers
      session_cache_limiter("public");
      self::sendHeader("Last-Modified: ".($lastModified->format("D, d M Y H:i:s")." GMT")." GMT");
      self::sendHeader("ETag: \"".$etag."\"");

      // check if page has changed and send 304 if not
      if (($ifModifiedSinceHeader !== false && strtotime($ifModifiedSinceHeader) == $lastModifiedTs) ||
              $etagHeader == $etag) {
        // client has current response already
        $response->setStatus(304);
        $responseSent = true;
      }
    }

    // delegate serialization to the response format
    if (!$responseSent) {
      $formatName = $response->getFormat();
      if (strlen($formatName) == 0) {
        // the format must be given!
        throw new ConfigurationException("No response format defined for ".$response->__toString());
      }
      $format = $this->getFormat($formatName);
      self::sendHeader("Content-Type: ".$format->getMimeType()."; charset=utf-8");
      foreach ($response->getHeaders() as $name => $value) {
        self::sendHeader($name.': '.$value);
      }
      $format->serialize($response);
    }

    http_response_code($response->getStatus());
  }

  /**
   * Send the given header
   * @param $header
   */
  protected function sendHeader($header) {
    if (!self::$headersSent) {
      header($header);
    }
  }
}
?>
