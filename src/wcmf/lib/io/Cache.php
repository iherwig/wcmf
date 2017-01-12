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
namespace wcmf\lib\io;

/**
 * Cache defines the interface for cache implementations.
 *
 * Caches are divided into different sections, which store
 * key value pairs.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface Cache {

  /**
   * Check if a key exists in the specified cache
   * @param $section The caching section
   * @param $key The cache key
   * @return boolean
   */
  public function exists($section, $key);

  /**
   * Get a value from the specified cache
   * @param $section The caching section
   * @param $key The cache key
   * @return Mixed
   */
  public function get($section, $key);

  /**
   * Store a value in the specified cache
   * @param $section The caching section
   * @param $key The key
   * @param $value The value
   */
  public function put($section, $key, $value);

  /**
   * Clear the given cache section. The wildcard char '*'
   * may be added to the section name in order to
   * clear all matching sections.
   * @param $section The caching section
   */
  public function clear($section);

  /**
   * Clear all cache sections
   */
  public function clearAll();
}
?>
