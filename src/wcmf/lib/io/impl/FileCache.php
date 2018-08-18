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

  private $cacheDir = null;
  private $cache = null;
  private $fileUtil = null;

  /**
   * Constructor
   * @param $cacheDir String
   */
  public function __construct($cacheDir=null) {
    $this->fileUtil = new FileUtil();
    $this->cacheDir = WCMF_BASE.$cacheDir;
  }

  /**
   * Set the cache directory (defaults to WCMF_BASE/app/cache/ if not given).
   * If not existing, the directory will be created relative to WCMF_BASE.
   * @param $cacheDir String
   */
  public function setCacheDir($cacheDir) {
    $this->cacheDir = WCMF_BASE.$cacheDir;
  }

  /**
   * @see Cache::exists()
   */
  public function exists($section, $key) {
    $this->initializeCache($section);
    return isset($this->cache[$section][$key]) &&
      !$this->isExpired($this->cache[$section][$key][0], $this->cache[$section][$key][1]);
  }

  /**
   * @see Cache::getDate()
   */
  public function getDate($section, $key) {
    $this->initializeCache($section);
    if (isset($this->cache[$section][$key]) &&
        !$this->isExpired($this->cache[$section][$key][0], $this->cache[$section][$key][1])) {
      return (new \DateTime())->setTimeStamp($this->cache[$section][$key][0]);
    }
    return null;
  }

  /**
   * @see Cache::get()
   */
  public function get($section, $key) {
    $this->initializeCache($section);
    if (isset($this->cache[$section][$key]) &&
        !$this->isExpired($this->cache[$section][$key][0], $this->cache[$section][$key][1])) {
      return $this->cache[$section][$key][2];
    }
    return null;
  }

  /**
   * @see Cache::put()
   */
  public function put($section, $key, $value, $lifetime=null) {
    $this->initializeCache($section);
    $this->cache[$section][$key] = [time(), $lifetime, $value];
    $this->saveCache($section);
  }

  /**
   * @see Cache::clear()
   */
  public function clear($section) {
    if (preg_match('/\*$/', $section)) {
      // handle wildcards
      $cachBaseDir = $this->getCacheDir();
      $directory = $cachBaseDir.dirname($section);
      if (is_dir($directory)) {
        $pattern = '/^'.preg_replace('/\*$/', '', $this->fileUtil->basename($section)).'/';
        $files = $this->fileUtil->getFiles($directory, $pattern, true, true);
        foreach ($files as $file) {
          $this->clear(str_replace($cachBaseDir, '', $file));
        }
        $directories = $this->fileUtil->getDirectories($directory, $pattern, true, true);
        foreach ($directories as $directory) {
          $this->clear(str_replace($cachBaseDir, '', $directory).'/*');
          @rmdir($directory);
        }
      }
    }
    else {
      $file = $this->getCacheFile($section);
      @unlink($file);
      unset($this->cache[$section]);
    }
  }

  /**
   * @see Cache::clearAll()
   */
  public function clearAll() {
    $cacheDir = $this->getCacheDir();
    if (is_dir($cacheDir)) {
      $this->fileUtil->emptyDir($cacheDir);
    }
    $this->cache = null;
  }

  /**
   * Initialize the cache
   * @param $section The caching section
   */
  private function initializeCache($section) {
    if (!isset($this->cache[$section])) {
      $file = $this->getCacheFile($section);
      if (file_exists($file)) {
        $this->cache[$section] = unserialize(file_get_contents($file));
      }
      else {
        $this->cache[$section] = [];
      }
    }
  }

  /**
   * Check if an entry with the given creation timestamp and lifetime is expired
   * @param $createTs The creation timestamp
   * @param $lifetime The lifetime in seconds
   * @return Boolean
   */
  private function isExpired($createTs, $lifetime) {
    if ($lifetime === null) {
      return false;
    }
    $expireDate = (new \DateTime())->setTimeStamp($createTs);
    if (intval($lifetime)) {
      $expireDate = $expireDate->modify('+'.$lifetime.' seconds');
    }
    return $expireDate < new \DateTime();
  }

  /**
   * Save the cache
   * @param $section The caching section
   */
  private function saveCache($section) {
    $content = serialize($this->cache[$section]);
    $file = $this->getCacheFile($section);
    $this->fileUtil->mkdirRec(dirname($file));
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
    if ($this->cacheDir == null) {
      $this->cacheDir = WCMF_BASE.'/app/cache/';
    }
    return $this->cacheDir;
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
