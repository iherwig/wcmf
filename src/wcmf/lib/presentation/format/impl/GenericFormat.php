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
namespace wcmf\lib\presentation\format\impl;

use wcmf\lib\io\Cache;
use wcmf\lib\presentation\format\Format;
use wcmf\lib\presentation\format\impl\CacheTrait;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;

/**
 * GenericFormat is used to output arbitrary responses. It prints the
 * content of the 'body' value of the response. The mime type is defined
 * by the 'mime_type' value of the response. The default cache section can
 * be set with the 'cache_section' value of the response.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class GenericFormat extends AbstractFormat {
  use CacheTrait;

  const CACHE_SECTION = 'genericformat';

  protected $cache = null;

  /**
   * Constructor
   * @param $dynamicCache Cache instance
   */
  public function __construct(Cache $dynamicCache) {
    $this->cache = $dynamicCache;
  }

  /**
   * @see Format::getMimeType()
   */
  public function getMimeType(Response $response=null) {
    return $response ? $response->getValue('mime_type') : '';
  }

  /**
   * @see CacheTrait::getCache()
   */
  protected function getCache() {
    return $this->cache;
  }

  /**
   * @see CacheTrait::getBaseCacheSection()
   */
  protected function getBaseCacheSection() {
    return self::CACHE_SECTION;
  }

  /**
   * @see AbstractFormat::deserializeValues()
   */
  protected function deserializeValues(Request $request) {
    return $request->getValues();
  }

  /**
   * @see AbstractFormat::serializeValues()
   */
  protected function serializeValues(Response $response) {
    return $response->getValues();
  }

  /**
   * @see AbstractFormat::afterSerialize()
   */
  protected function afterSerialize(Response $response) {
    // output response payload
    $caching = $this->isCaching($response);
    if (!$caching || !$this->isCached($response)) {
      $payload = $response->getValue('body');
      // cache result
      if ($caching) {
        $this->putInCache($response, $payload);
      }
    }
    else {
      $payload = $this->getFromCache($response);
    }
    print($payload);

    return parent::afterSerialize($response);
  }
}
?>
