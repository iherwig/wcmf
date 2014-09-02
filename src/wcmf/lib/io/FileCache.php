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
namespace wcmf\lib\io;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\io\FileUtil;

/**
 * FileCache provides simple caching functionality.
 *
 * The cache is divided into different sections. All sections share
 * the same base path.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class FileCache {

  private static $cacheDir = null;
  private static $cache = null;

  /**
   * Check if a key exists in the specified cache
   * @param $section The caching section
   * @param $key The cache key
   * @return boolean
   */
  public static function exists($section, $key) {
    self::initializeCache($section);
    return isset(self::$cache[$section][$key]);
  }

  /**
   * Get a value from the specified cache
   * @param $section The caching section
   * @param $key The cache key
   * @return Mixed
   */
  public static function get($section, $key) {
    self::initializeCache($section);
    if (isset(self::$cache[$section][$key])) {
      return self::$cache[$section][$key];
    }
    return null;
  }

  /**
   * Store a value in the specified cache
   * @param $section The caching section
   * @param $key The key
   * @param $value The value
   */
  public static function put($section, $key, $value) {
    self::initializeCache($section);
    self::$cache[$section][$key] = $value;
    self::saveCache($section);
  }

  /**
   * Clear the given cache section
   * @param $section The caching section
   */
  public static function clear($section) {
    $file = self::getCacheFile($section);
    @unlink($file);
  }

  /**
   * Clear all cache sections
   */
  public static function clearAll() {
    $cacheDir = self::getCacheDir();
    if (is_dir($cacheDir)) {
      FileUtil::emptyDir($cacheDir);
    }
  }

  /**
   * Initialize the cache
   * @param $section The caching section
   */
  private static function initializeCache($section) {
    if (!isset(self::$cache[$section])) {
      $file = self::getCacheFile($section);
      if (file_exists($file)) {
        self::$cache[$section] = unserialize(file_get_contents($file));
      }
      else {
        self::$cache[$section] = array();
      }
    }
  }

  /**
   * Save the cache
   * @param $section The caching section
   */
  private static function saveCache($section) {
    $content = serialize(self::$cache[$section]);
    $file = self::getCacheFile($section);
    FileUtil::mkdirRec(dirname($file));
    $fh = fopen($file, "w");
    if ($fh !== false) {
      fwrite($fh, $content);
      fclose($fh);
    }
    else {
      throw new ConfigurationException("The cache path is not writable: ".$file);
    }
  }

  /**
   * Get the cache root directory
   * @return String
   */
  private static function getCacheDir() {
    if (self::$cacheDir == null) {
      $config = ObjectFactory::getConfigurationInstance();
      if ($config) {
        if ((self::$cacheDir = $config->getDirectoryValue('cacheDir', 'application')) === false) {
          throw new ConfigurationException("No cache path 'cacheDir' defined in ini section 'application'.");
        }
      }
      else {
        self::$cacheDir = session_save_path();
      }
    }
    return self::$cacheDir;
  }

  /**
   * Get the cache file for the specified cache
   * @param $section The caching section
   * @return String
   */
  private static function getCacheFile($section) {
    return self::getCacheDir().$section;
  }
}
?>
