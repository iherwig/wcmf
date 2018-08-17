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
namespace wcmf\lib\presentation\format\impl;

use wcmf\lib\io\Cache;
use wcmf\lib\presentation\Response;

/**
 * CacheTrait adds support for cached responses.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
trait CacheTrait {
  protected $cache = null;

  /**
   * Get the cache to use
   * @return Cache
   */
  protected abstract function getCache();

  /**
   * @see Format::isCached()
   */
  public function isCached(Response $response) {
    $cacheId = $response->getCacheId();
    return $this->getCache()->exists($this->getCacheSection($response), $cacheId);
  }

  /**
   * @see Format::getCacheDate()
   */
  public function getCacheDate(Response $response) {
    $cacheId = $response->getCacheId();
    return $this->getCache()->getDate($this->getCacheSection($response), $cacheId);
  }

  /**
   * Get the cache base section for the concreate format
   * @note Sublcasses override this to define custom base cache sections
   * @return String
   */
  protected function getBaseCacheSection() {
    return 'cachedformat';
  }

  /**
   * Get the cache section for a response
   * @param Response $response
   * @return String
   */
  protected function getCacheSection(Response $response) {
    $cacheId = $response->getCacheId();
    return $this->getBaseCacheSection().'-'.$cacheId;
  }

  /**
   * Check if the response should be cached.
   * @param $response
   * @param Boolean
   */
  protected function isCaching(Response $response) {
    return strlen($response->getCacheId()) > 0;
  }

  /**
   * Store the payload of the response in the cache.
   * @param $response
   * @param $payload String
   */
  protected function putInCache(Response $response, $payload) {
    $this->getCache()->put($this->getCacheSection($response), $response->getCacheId(), $payload, $response->getCacheLifetime());
  }

  /**
   * Load the payload of the response from the cache.
   * @param $response
   * @return String
   */
  protected function getFromCache(Response $response) {
    return $this->getCache()->get($this->getCacheSection($response), $response->getCacheId());
  }
}
?>
