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
namespace wcmf\lib\presentation\control\lists\impl;

use wcmf\lib\io\Cache;
use wcmf\lib\config\ConfigurationException;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\StringQuery;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\ObjectId;
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
 * // list all authors and display only the name (instead of default display values)
 * {"type":"node","types":["Author"],"displayValues":["name"]}
 *
 * // list all authors and books
 * {"type":"node","types":["Author","Book"]}
 *
 * // list all authors with name starting with A (see StringQuery)
 * {"type":"node","types":["Author"],"query":"Author.name LIKE 'A%'"}
 *
 * // list all authors and books with different queries (see StringQuery)
 * {"type":"node","types":["Author"],"query":{"Author":"Author.name LIKE 'A%'","Book":"Book.title LIKE 'A%'"}}
 *
 * // list all authors and books with specified display values
 * {"type":"node","types":["Author","Book"],"displayValues":{"Author":["name"],"Book":["title"]}}
 *
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
  public function getList($options, $valuePattern=null, $key=null, $language=null) {
    if (!isset($options['types'])) {
      throw new ConfigurationException("No 'types' given in list options: ".StringUtil::getDump($options));
    }
    $localization = ObjectFactory::getInstance('localization');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $types = $options['types'];

    $isSingleType = sizeof($types) == 1;
    $hasQuery = (isset($options['query']) && !empty($options['query'])) || $valuePattern || $key;
    $needsTranslation = $language != null && $language != $localization->getDefaultLanguage();

    // set cache id for unfiltered result
    $cacheId = !$hasQuery ? hash('sha256', join('+', [json_encode($options), $valuePattern, $key, $language])) : null;

    $list = [];
    if ($cacheId && $this->cache->exists(self::CACHE_SECTION, $cacheId)) {
      $list = $this->cache->get(self::CACHE_SECTION, $cacheId);
    }
    else {
      if ($key) {
        // load single object
        $type = $types[0];
        $oid = $isSingleType ? new ObjectId($type, $key) : ObjectId::parse($key);
        $object = $persistenceFacade->load($oid);
        $list[$key] = $object ? $this->getDisplayValue($type, $object, $options) : '';
      }
      else {
        foreach ($types as $type) {
          $query = new StringQuery($type);
          if ($hasQuery) {
            $queryValue = $options['query'];
            if (is_string($queryValue)) {
              $query->setConditionString($queryValue);
            }
            else if (isset($queryValue[$type])) {
              $query->setConditionString($queryValue[$type]);
            }
          }
          // add display value query
          if ($valuePattern) {
            $dbPattern = preg_replace('/^\/|\/$/', '', $valuePattern);
            $valuePatterns = [];
            $mapper = $persistenceFacade->getMapper($type);
            foreach ($mapper->getProperties()['displayValues'] as $displayValue) {
              $valuePatterns[] = $type.'.'.$displayValue.' REGEXP '.$mapper->quoteValue($dbPattern);
            }
            if (sizeof($valuePatterns) > 0) {
              $existingQuery = $query->getConditionString();
              $query->setConditionString($existingQuery.(strlen($existingQuery) > 0 ? ' AND ' : ' ').'('.join(' OR ', $valuePatterns).')');
            }
          }
          $objects = $query->execute(BuildDepth::SINGLE);
          foreach ($objects as $object) {
            if ($needsTranslation) {
              $object = $localization->loadTranslation($object, $language);
            }
            $id = $isSingleType ? $object->getOID()->getFirstId() : $object->getOID()->__toString();
            $list[$id] = $this->getDisplayValue($type, $object, $options);
          }
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

  /**
   * Get the display value for an object
   * @param $type
   * @param $object
   * @param $options
   * @return string
   */
  protected function getDisplayValue($type, $object, $options) {
    if (isset($options['displayValues'])) {
      $displayValues = $options['displayValues'];
      $typeDisplayValues = isset($displayValues[$type]) ? $displayValues[$type] : $displayValues;
      $object->setProperty('displayValues', $typeDisplayValues);
    }
    return $object->getDisplayValue();
  }
}
?>
