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
namespace wcmf\lib\io\impl;

use wcmf\lib\io\Cache;

/**
 * NullCache acts as no cache.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NullCache implements Cache {

  /**
   * @see Cache::exists()
   */
  public function exists($section, $key) {
    return false;
  }

  /**
   * @see Cache::get()
   */
  public function get($section, $key) {
    return null;
  }

  /**
   * @see Cache::put()
   */
  public function put($section, $key, $value, $lifetime=null) {}

  /**
   * @see Cache::clear()
   */
  public function clear($section) {}

  /**
   * @see Cache::clearAll()
   */
  public function clearAll() {}
}
?>
