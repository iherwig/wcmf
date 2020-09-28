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
namespace wcmf\lib\io\impl;

use wcmf\lib\io\Cache;
use wcmf\lib\io\FileUtil;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

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
  private $caches = [];

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
    $cache = $this->getCache($section);
    return $cache->hasItem($this->sanitizeKey($key));
  }

  /**
   * @see Cache::getDate()
   */
  public function getDate($section, $key) {
    $cache = $this->getCache($section);
    $item = $cache->getItem($this->sanitizeKey($key));
    if ($item->isHit()) {
      return (new \DateTime())->setTimeStamp($item->get()[0]);
    }
    return null;
  }

  /**
   * @see Cache::get()
   */
  public function get($section, $key) {
    $cache = $this->getCache($section);
    $item = $cache->getItem($this->sanitizeKey($key));
    return $item->isHit() ? $item->get()[1] : null;
  }

  /**
   * @see Cache::put()
   */
  public function put($section, $key, $value, $lifetime=null) {
    $cache = $this->getCache($section);
    $item = $cache->getItem($this->sanitizeKey($key));
    $item->set([time(), $value]);
    if ($lifetime !== null) {
      $item->expiresAfter($lifetime);
    }
    $cache->save($item);
  }

  /**
   * @see Cache::clear()
   */
  public function clear($section) {
    if (preg_match('/\*$/', $section)) {
      // handle wildcards
      $cachBaseDir = $this->getCacheDir();
      if (is_dir($cachBaseDir)) {
        $pattern = '/^'.preg_replace('/\*$/', '', $section).'/';
        $directories = $this->fileUtil->getDirectories($cachBaseDir, $pattern, true, true);
        foreach ($directories as $directory) {
          $this->fileUtil->emptyDir($directory);
        }
      }
    }
    else {
      $cache = $this->getCache($section);
      $cache->clear();
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
   * Get the cache for the specified section
   * @param $section The caching section
   * @return FilesystemAdapter
   */
  private function getCache($section) {
    $section = $this->sanitizeSection($section);
    if (!isset($this->caches[$section])) {
      $this->caches[$section] = new FilesystemAdapter($section, 0, $this->cacheDir);
    }
    return $this->caches[$section];
  }

  private function sanitizeSection($section) {
    return preg_replace('/[^-+_.A-Za-z0-9]/', '_', $section);
  }

  private function sanitizeKey($key) {
    return strlen($key) === 0 ? '.' : preg_replace('/[\{\}\(\)\/@\:]/', '_', $key);
  }
}
?>
