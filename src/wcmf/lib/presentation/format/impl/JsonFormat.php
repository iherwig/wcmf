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
namespace wcmf\lib\presentation\format\impl;

use wcmf\lib\core\LogTrait;
use wcmf\lib\io\Cache;
use wcmf\lib\model\NodeSerializer;
use wcmf\lib\presentation\format\impl\CacheTrait;
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
  use LogTrait;
  use CacheTrait;

  const CACHE_SECTION = 'jsonformat';

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
  }

  /**
   * @see Format::getMimeType()
   */
  public function getMimeType(Response $response=null) {
    return 'application/json';
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
   * @see AbstractFormat::afterSerialize()
   */
  protected function afterSerialize(Response $response) {
    // output response payload
    $caching = $this->isCaching($response);
    if (!$caching || !$this->isCached($response)) {
      // encode data
      $payload = json_encode($response->getValues());
      if (self::logger()->isDebugEnabled()) {
        self::logger()->debug($response->getValues());
        self::logger()->debug($payload);
      }
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
