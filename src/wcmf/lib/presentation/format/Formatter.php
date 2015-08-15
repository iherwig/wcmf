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
namespace wcmf\lib\presentation\format;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;

/**
 * Formatter is the single entry point for request/response formatting.
 * It chooses the configured formatter based on the format property of the request
 * by getting the value XXXFormat from the configuration section 'formats'.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Formatter {

  private static $_headersSent = false;

  /**
   * Deserialize Request data into objects.
   * @param $request A reference to the Request instance
   */
  public static function deserialize(Request $request) {

    // get the formatter that should be used for this request format
    $format = $request->getFormat();
    if ($format == null) {
      // the format must be given!
      throw new ConfigurationException("No content format defined for ".$request->__toString());
    }
    $format->deserialize($request);
  }

  /**
   * Serialize Response according to the output format.
   * @param $response A reference to the Response instance
   */
  public static function serialize(Response $response) {
    self::$_headersSent = headers_sent();
    self::sendHeader('HTTP/1.1 '.$response->getStatus());

    // if the response has a file, we send it and return
    $file = $response->getFile();
    if ($file) {
      self::sendHeader("Content-Type: application/octet-stream");
      self::sendHeader('Content-Disposition: attachment; filename="'.basename($file['filename']).'"');
      self::sendHeader("Pragma: no-cache");
      self::sendHeader("Expires: 0");
      echo $file['content'];
      return;
    }

    // default: delegate to response format
    $format = $response->getFormat();
    if ($format == null) {
      // the response format must be given!
      throw new ConfigurationException("No response format defined for ".$response->__toString());
    }
    self::sendHeader("Content-Type: ".$format->getMimeType()."; charset=utf-8");
    foreach ($response->getHeaders() as $name => $value) {
      self::sendHeader($name.': '.$value);
    }
    $format->serialize($response);
  }

  /**
   * Send the given header
   * @param $header
   */
  protected static function sendHeader($header) {
    if (!self::$_headersSent) {
      header($header);
    }
  }
}
?>
