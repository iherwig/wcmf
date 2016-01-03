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

  private static $_headersSent = false;

  private $_formats = array();

  /**
   * Constructor
   * @param $formats Array of Format instances to use
   */
  public function __construct(array $formats) {
    $this->_formats = $formats;
  }

  /**
   * @see Formatter::getFormatFromMimeType()
   */
  public function getFormatFromMimeType($mimeType) {
    $firstFormat = null;
    foreach ($this->_formats as $name => $instance) {
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
    self::$_headersSent = headers_sent();
    self::sendHeader(self::getStatusHeader($response->getStatus()));

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

  /**
   * Send the given header
   * @param $header
   */
  protected function sendHeader($header) {
    if (!self::$_headersSent) {
      header($header);
    }
  }

  /**
   * Get the format with the given name
   * @param $name
   * @return Format
   */
  protected function getFormat($name) {
    if (isset($this->_formats[$name])) {
      return $this->_formats[$name];
    }
    throw new ConfigurationException("Configuration section 'Formats' does not contain a format definition with name: ".$name);
  }

  /**
   * Get the http header for the given status code
   * @param $code
   * @return String
   */
  protected function getStatusHeader($code) {
    switch ($code) {
      case 100: $text = 'Continue'; break;
      case 101: $text = 'Switching Protocols'; break;
      case 200: $text = 'OK'; break;
      case 201: $text = 'Created'; break;
      case 202: $text = 'Accepted'; break;
      case 203: $text = 'Non-Authoritative Information'; break;
      case 204: $text = 'No Content'; break;
      case 205: $text = 'Reset Content'; break;
      case 206: $text = 'Partial Content'; break;
      case 300: $text = 'Multiple Choices'; break;
      case 301: $text = 'Moved Permanently'; break;
      case 302: $text = 'Moved Temporarily'; break;
      case 303: $text = 'See Other'; break;
      case 304: $text = 'Not Modified'; break;
      case 305: $text = 'Use Proxy'; break;
      case 400: $text = 'Bad Request'; break;
      case 401: $text = 'Unauthorized'; break;
      case 402: $text = 'Payment Required'; break;
      case 403: $text = 'Forbidden'; break;
      case 404: $text = 'Not Found'; break;
      case 405: $text = 'Method Not Allowed'; break;
      case 406: $text = 'Not Acceptable'; break;
      case 407: $text = 'Proxy Authentication Required'; break;
      case 408: $text = 'Request Time-out'; break;
      case 409: $text = 'Conflict'; break;
      case 410: $text = 'Gone'; break;
      case 411: $text = 'Length Required'; break;
      case 412: $text = 'Precondition Failed'; break;
      case 413: $text = 'Request Entity Too Large'; break;
      case 414: $text = 'Request-URI Too Large'; break;
      case 415: $text = 'Unsupported Media Type'; break;
      case 500: $text = 'Internal Server Error'; break;
      case 501: $text = 'Not Implemented'; break;
      case 502: $text = 'Bad Gateway'; break;
      case 503: $text = 'Service Unavailable'; break;
      case 504: $text = 'Gateway Time-out'; break;
      case 505: $text = 'HTTP Version not supported'; break;
      default:
        exit('Unknown http status code "'.htmlentities($code).'"');
      break;
    }
    $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
    return $protocol.' '.$code.' '.$text;
  }
}
?>
