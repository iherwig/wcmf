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
namespace wcmf\lib\io\impl;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\io\Cache;
use wcmf\lib\io\FileUtil;

/**
 * FileCache provides simple caching functionality.
 *
 * The cache is divided into different sections. All sections share
 * the same base path.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class FileCache implements Cache {

  private $_cacheDir = null;
  private $_cache = null;

  /**
   * Set the cache directory (defaults to session_save_path if not given).
   * @param $cacheDir String
   */
  public function setCacheDir($cacheDir) {
    $this->_cacheDir = $cacheDir;
  }

  /**
   * @see Cache::exists()
   */
  public function exists($section, $key) {
    $this->initializeCache($section);
    return isset($this->_cache[$section][$key]);
  }

  /**
   * @see Cache::get()
   */
  public function get($section, $key) {
    $this->initializeCache($section);
    if (isset($this->_cache[$section][$key])) {
      return $this->_cache[$section][$key];
    }
    return null;
  }

  /**
   * @see Cache::put()
   */
  public function put($section, $key, $value) {
    $this->initializeCache($section);
    $this->_cache[$section][$key] = $value;
    $this->saveCache($section);
  }

  /**
   * @see Cache::clear()
   */
  public function clear($section) {
    $file = $this->getCacheFile($section);
    @unlink($file);
    unset($this->_cache[$section]);
  }

  /**
   * @see Cache::clearAll()
   */
  public function clearAll() {
    $cacheDir = $this->getCacheDir();
    if (is_dir($cacheDir)) {
      FileUtil::emptyDir($cacheDir);
    }
    $this->_cache = null;
  }

  /**
   * Initialize the cache
   * @param $section The caching section
   */
  private function initializeCache($section) {
    if (!isset($this->_cache[$section])) {
      $file = $this->getCacheFile($section);
      if (file_exists($file)) {
        $this->_cache[$section] = unserialize(file_get_contents($file));
      }
      else {
        $this->_cache[$section] = array();
      }
    }
  }

  /**
   * Save the cache
   * @param $section The caching section
   */
  private function saveCache($section) {
    $content = serialize($this->_cache[$section]);
    $file = $this->getCacheFile($section);
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
  private function getCacheDir() {
    if ($this->_cacheDir == null) {
      $this->_cacheDir = session_save_path().DIRECTORY_SEPARATOR;
    }
    return $this->_cacheDir;
  }

  /**
   * Get the cache file for the specified cache
   * @param $section The caching section
   * @return String
   */
  private function getCacheFile($section) {
    return $this->getCacheDir().$section;
  }
}
?>