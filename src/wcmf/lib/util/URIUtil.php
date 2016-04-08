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
namespace wcmf\lib\util;

/**
 * URIUtil provides support for uri manipulation.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class URIUtil {

  /**
   * Convert an absolute URI to a relative
   * code from http://www.webmasterworld.com/forum88/334.htm
   * @param $absUri Absolute URI to convert, may have a trailing filename
   * @param $base Base URI
   */
  public static function makeRelative($absUri, $base) {
    // normalize slashes and remove drive names
    list($absUri, $base) = self::normalizePaths(
            self::removeProtocols(self::normalizeSlashes(array($absUri, $base))));

    // add slash to base if missing
    if (!preg_match('/\/$/', $base)) {
      $base .= '/';
    }
    $absArray = explode('/', $absUri);
    $baseArray = explode('/', $base);

    // remove trailing file names
    $fileName = '';
    if (strrpos($absUri, '/') !== strlen($absUri)) {
      $fileName = array_pop($absArray);
    }
    if (strrpos($base, '/') !== strlen($base)) {
      array_pop($baseArray);
    }

    // ignore common path
    while (sizeof($absArray) > 0 && sizeof($baseArray) > 0 && $absArray[0] == $baseArray[0]) {
      array_shift($absArray);
      array_shift($baseArray);
    }

    // construct connecting path
    $relUri = self::normalizePaths(str_repeat('../', sizeof($baseArray)).join('/', $absArray).'/'.$fileName);
    return $relUri;
  }

  /**
   * Convert a relative URI to an absolute
   * code from http://99webtools.com/relative-path-into-absolute-url.php
   * @param $relUri Relative URI to convert
   * @param $base Base URI
   */
  public static function makeAbsolute($relUri, $base) {
    list($relUri, $base) = self::normalizeSlashes(array($relUri, $base));

    // return if already absolute URL
    if (parse_url($relUri, PHP_URL_SCHEME) != '') {
      return $relUri;
    }
    // add slash to base if missing
    if (!preg_match('/\/$/', $base)) {
      $base .= '/';
    }
    $firstChar = (strlen($relUri) > 0) ? substr($relUri, 0, 1) : '';
    // queries and anchors
    if ($firstChar == '#' || $firstChar == '?') {
      return $base.$relUri;
    }
    // parse base URL and convert to local variables: $scheme, $host, $path
    extract(parse_url($base));
    $scheme = !isset($scheme) ? 'http' : $scheme;
    $host = !isset($host) ? '' : $host;
    $path = !isset($path) ? '' : $path;
    // remove non-directory element from path
    $path = preg_replace('#/[^/]*$#', '', $path);
    // destroy path if relative url points to root
    if ($firstChar == '/') {
      $path = '';
    }
    // dirty absolute URL
    $abs = "$host$path/$relUri";
    // normalize
    $abs = self::normalizePaths($abs);
    // absolute URL is ready!
    return $scheme.'://'.$abs;
  }

  /**
   * Translate a relative URI from one location to the script location.
   * For example if a file path is stored relative to location A and should be
   * translated to the script URI (location B), use
   * URIUtil::translate($filepathAsSeenFromA, $pathFromBtoA)
   * @param $pathFromA Relative URI to translate as seen from base
   * @param $pathFromScriptToA Base URI
   * @return An associative array with keys 'absolute' and 'relative'
   * and the absolute and relative URI (as seen from the executed script) as values
   */
  public static function translate($pathFromA, $pathFromScriptToA) {
    list($pathFromA, $pathFromScriptToA) = self::normalizeSlashes(array($pathFromA, $pathFromScriptToA));

    $self = self::getProtocolStr().$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];
    $path = dirname($self).'/';
    $absUrl = self::makeAbsolute($pathFromA, $path.$pathFromScriptToA);
    $relUrl = self::makeRelative($absUrl, $path);

    return array('absolute' => $absUrl, 'relative' => $relUrl);
  }

  /**
   * Check if an url is available (HTTP-Code: 200)
   * @note requires cURL library
   * @param $url The url to check
   * @param $timeout The timeout in seconds (default: 5)
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

  /**
   * Normalize slashes
   * @param $paths Path to normalize or array of paths
   */
  public static function normalizeSlashes($paths) {
    return preg_replace(
            array("/\\\\/"), array('/'),
            $paths);
  }

  /**
   * Remove protocols
   * @param $paths Path to normalize or array of paths
   */
  public static function removeProtocols($paths) {
    return preg_replace(array("/^[^:]{1}:/"), array(''), $paths);
  }

  /**
   * Normalize paths (replace '//' or '/./' or '/foo/../' with '/')
   * @param $paths Path to normalize or array of paths
   */
  public static function normalizePaths($paths) {
    $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
    for ($n=1; $n>0; $paths=preg_replace($re, '/', $paths, -1, $n)) {}
    return $paths;
  }
}
?>
