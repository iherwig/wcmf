<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\util;

use wcmf\lib\core\Log;

/**
 * URIUtil provides support for uri manipulation.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class URIUtil {

  /**
   * Convert an absolute URI to a relative
   * code from http://www.webmasterworld.com/forum88/334.htm
   * @param abs_uri Absolute URI to convert
   * @param base Base URI
   */
  public static function makeRelative($abs_uri, $base) {
    $abs_uri = preg_replace("{^[^:]+://[^/]+}", '', $abs_uri);
    $base = preg_replace("{^[^:]+://[^/]+}", '', $base);

    $abs_array = explode('/', $abs_uri);
    $base_array = explode('/', $base);

    // remove trailing file names
    $fileName = '';
    if (strrpos($abs_uri, '/') !== strlen($abs_uri)) {
      $fileName = array_pop($abs_array);
    }
    if (strrpos($base, '/') !== strlen($base)) {
      array_pop($base_array);
    }
    // ignore common path
    while (sizeof($abs_array) > 0 && sizeof($base_array) > 0 && $abs_array[0] == $base_array[0]) {
      array_shift($abs_array);
      array_shift($base_array);
    }

    // construct connecting path
    $rel_uri = str_repeat('../', sizeof($base_array)).join('/', $abs_array).'/'.$fileName;
    return $rel_uri;
  }

  /**
   * Convert a relative URI to an absolute
   * code from http://99webtools.com/relative-path-into-absolute-url.php
   * @param rel_uri Relative URI to convert
   * @param base Base URI
   */
  public static function makeAbsolute($rel_uri, $base) {
    if(strpos($rel_uri, "//") === 0) {
      return "http:".$rel_uri;
    }
    /* return if already absolute URL */
    if (parse_url($rel_uri, PHP_URL_SCHEME) != '') {
      return $rel_uri;
    }
    /* add slash to base if missing */
    if (!preg_match('/\/$/', $base)) {
      $base .= '/';
    }
    $firstChar = (strlen($rel_uri) > 0) ? substr($rel_uri, 0, 1) : '';
    /* queries and anchors */
    if ($firstChar == '#' || $firstChar == '?') {
      return $base.$rel_uri;
    }
    /* parse base URL and convert to local variables: $scheme, $host, $path */
    extract(parse_url($base));
    /* remove non-directory element from path */
    $path = preg_replace('#/[^/]*$#', '', $path);
    /* destroy path if relative url points to root */
    if ($firstChar == '/') {
      $path = '';
    }
    /* dirty absolute URL */
    $abs = "$host$path/$rel_uri";
    /* replace '//' or '/./' or '/foo/../' with '/' */
    $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
    for ($n=1; $n>0; $abs=preg_replace($re, '/', $abs, -1, $n)) {}
    /* absolute URL is ready! */
    return $scheme.'://'.$abs;
  }

  /**
   * Translate a relative URI from one location to the script location.
   * For example if a file path is stored relative to location A and should be
   * translated to the script URI (location B), use
   * URIUtil::translate($filepathAsSeenFromA, $pathFromBtoA)
   * @param rel_uri Relative URI to translate as seen from base
   * @param base Base URI
   * @return An associtative array with keys 'absolute' and 'relative'
   * and the absolute and relative URI (as seen from the executed script) as values
   */
  public static function translate($rel_uri, $base) {
    $self = UriUtil::getProtocolStr().$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];
    $path = dirname($self).'/';
    $absUrl = self::makeAbsolute($rel_uri, $path.$base);
    $relUrl = self::makeRelative($absUrl, $self);

    return array('absolute' => $absUrl, 'relative' => $relUrl);
  }

  /**
   * Check if an url is available (HTTP-Code: 200)
   * @note requires cURL library
   * @param url The url to check
   * @param timeout The timeout in seconds (default: 5)
   * @return Boolean whether the url is available
   */
  public static function validateUrl($url, $timeout=5) {
    $url_parts = @parse_url($url);
    // check local relative url
    if (empty($url_parts["host"])) {
      $fh = @fopen($url, "r");
      return ($fh !== false);
    }

    // check remote url
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    $r = curl_exec($ch);
    $headers = split("\n", $r);
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug("$url: HTTP-Response: ".  json_encode($headers), __CLASS__);
    }

    preg_match('/.+ ([0-9]{3}) .+/', $headers[0], $matches);
    return (intval($matches[1]) < 400);
  }

  /*
   * Get the protocol string (http:// or https://)
   * @return The protocol string
   */
  public static function getProtocolStr() {
    if (isset($_SERVER['HTTPS']) && strlen($_SERVER['HTTPS']) > 0 && $_SERVER['HTTPS'] != 'off') {
      return 'https://';
    }
    else {
      return 'http://';
    }
  }

  /**
   * Get the current page url
   * @return The url of the page
   */
  public static function getPageURL() {
    $pageURL = self::getProtocolStr();
    if ($_SERVER["SERVER_PORT"] != "80") {
      $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
    }
    else {
      $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
   }
   return $pageURL;
  }
}
?>
