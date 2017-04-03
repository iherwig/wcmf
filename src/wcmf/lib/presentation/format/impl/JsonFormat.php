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

use wcmf\lib\core\LogManager;
use wcmf\lib\io\Cache;
use wcmf\lib\model\NodeSerializer;
use wcmf\lib\presentation\format\impl\HierarchicalFormat;
use wcmf\lib\presentation\Response;

/**
 * JsonFormat implements the JSON request/response format. All data will
 * be serialized using the json_encode method except for Nodes.
 * Nodes are serialized into an array before encoding (see JsonFormat::serializeValue)
 * using the NodeSerializer class.
 * On serialization the data will be outputted directly using the print command.
 *
 * JsonFormat collects the response data from all executed controllers
 * into one response array and returns it all at once at the end of
 * script execution. This prevents from having multiple chunks of JSON
 * from each controller response that can't be decoded by clients.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class JsonFormat extends HierarchicalFormat {

  const CACHE_SECTION = 'jsonformat';

  private static $logger = null;

  protected $cache = null;
  protected $serializer = null;

  /**
   * Constructor
   * @param $serializer NodeSerializer instance
   * @param $dynamicCache Cache instance
   */
  public function __construct(NodeSerializer $serializer, Cache $dynamicCache) {
    $this->serializer = $serializer;
    $this->cache = $dynamicCache;
    if (self::$logger == null) {
      self::$logger = LogManager::getLogger(__CLASS__);
    }
  }

  /**
   * Get the cache section for a response
   * @param Response $response
   * @return String
   */
  protected function getCacheSection(Response $response) {
    $cacheId = $response->getCacheId();
    return self::CACHE_SECTION.'-'.$cacheId;
  }

  /**
   * @see Format::getMimeType()
   */
  public function getMimeType() {
    return 'application/json';
  }

  /**
   * @see Format::isCached()
   */
  public function isCached(Response $response) {
    $cacheId = $response->getCacheId();
    return $this->cache->exists($this->getCacheSection($response), $cacheId);
  }

  /**
   * @see Format::isCached()
   */
  public function getCacheDate(Response $response) {
    $cacheId = $response->getCacheId();
    return $this->cache->getDate($this->getCacheSection($response), $cacheId);
  }

  /**
   * @see HierarchicalFormat::afterSerialize()
   */
  protected function afterSerialize(Response $response) {
    $values = $response->getValues();

    $cacheId = $response->getCacheId();
    $caching = strlen($cacheId) > 0;
    if (!$caching || !$this->isCached($response)) {
      // encode data
      $encoded = json_encode($values);
      if (self::$logger->isDebugEnabled()) {
        self::$logger->debug($values);
        self::$logger->debug($encoded);
      }

      // cache result
      if ($caching) {
        $this->cache->put($this->getCacheSection($response), $cacheId, $encoded);
      }
    }
    else {
      $encoded = $this->cache->get($this->getCacheSection($response), $cacheId);
    }

    // render
    print($encoded);

    return $values;
  }

  /**
   * @see HierarchicalFormat::isSerializedNode()
   */
  protected function isSerializedNode($value) {
    return $this->serializer->isSerializedNode($value);
  }

  /**
   * @see HierarchicalFormat::serializeNode()
   */
  protected function serializeNode($value) {
    $node = $this->serializer->serializeNode($value);
    return $node;
  }

  /**
   * @see HierarchicalFormat::deserializeNode()
   */
  protected function deserializeNode($value) {
    $result = $this->serializer->deserializeNode($value);
    return $result;
  }
}
?>
