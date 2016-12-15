<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2016 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\io\impl;

use wcmf\lib\config\ConfigChangeEvent;
use wcmf\lib\config\ConfigurationException;
use wcmf\lib\core\ObjectFactory;
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
   */
  public function __construct() {
    $this->fileUtil = new FileUtil();
    ObjectFactory::getInstance('eventManager')->addListener(ConfigChangeEvent::NAME,
      array($this, 'configChanged'));
  }

  /**
   * Destructor.
   */
  public function __destruct() {
    ObjectFactory::getInstance('eventManager')->removeListener(ConfigChangeEvent::NAME,
      array($this, 'configChanged'));
  }

  /**
   * Set the cache directory (defaults to session_save_path if not given).
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
    return isset($this->cache[$section][$key]);
  }

  /**
   * @see Cache::get()
   */
  public function get($section, $key) {
    $this->initializeCache($section);
    if (isset($this->cache[$section][$key])) {
      return $this->cache[$section][$key];
    }
    return null;
  }

  /**
   * @see Cache::put()
   */
  public function put($section, $key, $value) {
    $this->initializeCache($section);
    $this->cache[$section][$key] = $value;
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
        $pattern = '/^'.preg_replace('/\*$/', '', basename($section)).'/';
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
        $this->cache[$section] = array();
      }
    }
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
      $this->cacheDir = session_save_path().DIRECTORY_SEPARATOR;
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

  /**
   * Listen to ConfigChangeEvents
   * @param $event ConfigChangeEvent instance
   */
  public function configChanged(ConfigChangeEvent $event) {
    $this->clearAll();
  }
}
?>
