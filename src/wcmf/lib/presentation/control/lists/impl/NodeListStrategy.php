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
namespace wcmf\lib\presentation\control\lists\impl;

use wcmf\lib\io\Cache;
use wcmf\lib\config\ConfigurationException;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\StringQuery;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\presentation\control\lists\ListStrategy;
use wcmf\lib\util\StringUtil;

/**
 * NodeListStrategy implements a list of entities that is retrieved
 * from the store, where the keys are the object ids and the
 * values are the display values.
 *
 * Configuration examples:
 * @code
 * // list all authors
 * {"type":"node","types":["Author"]}
 *
 * // list all authors and books
 * {"type":"node","types":["Author","Book"]}
 *
 * // list all authors with name starting with A (see StringQuery)
 * {"type":"node","types":["Author"],"query":"Author.name LIKE 'A%'"}
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NodeListStrategy implements ListStrategy {

  const CACHE_SECTION = 'nodeliststrategy';

  protected $cache = null;

  /**
   * Constructor
   * @param $dynamicCache Cache instance
   */
  public function __construct(Cache $dynamicCache) {
    $this->cache = $dynamicCache;
  }

  /**
   * @see ListStrategy::getList
   * $options is an associative array with keys 'types' and 'query' (optional)
   */
  public function getList($options, $language=null) {
    if (!isset($options['types'])) {
      throw new ConfigurationException("No 'types' given in list options: "+StringUtil::getDump($options));
    }
    $types = $options['types'];

    $isSingleType = sizeof($types) == 1;
    $hasQuery = isset($options['query']) && strlen($options['query']) > 0;

    $localization = $language != null ? ObjectFactory::getInstance('localization') : null;

    // set cache id for unfiltered result
    $cacheId = !$hasQuery ? md5(json_encode($options)) : null;

    $list = [];
    if ($cacheId && $this->cache->exists(self::CACHE_SECTION, $cacheId)) {
      $list = $this->cache->get(self::CACHE_SECTION, $cacheId);
    }
    else {
      foreach ($types as $type) {
        $query = new StringQuery($type);
        if ($hasQuery) {
          $query->setConditionString($options['query']);
        }
        $objects = $query->execute(BuildDepth::SINGLE);
        foreach ($objects as $object) {
          if ($language != null) {
            $object = $localization->loadTranslation($object, $language);
          }
          $id = $isSingleType ? $object->getOID()->getFirstId() : $object->getOID()->__toString();
          $list[$id] = $object->getDisplayValue();
        }
      }
      if ($cacheId) {
        $this->cache->put(self::CACHE_SECTION, $cacheId, $list);
      }
    }
    return $list;
  }

  /**
   * @see ListStrategy::isStatic
   */
  public function isStatic($options) {
    return false;
  }
}
?>
