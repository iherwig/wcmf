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
 * SuggestListStrategy implements a list of suggestions that is retrieved
 * from all existing values of one entity attribute, where the keys and the
 * values are the values.
 *
 * Configuration examples:
 * @code
 * // list all existing author names
 * {"type":"suggest","attributes":["Author.name"]}
 *
 * // list all existing author names and book titles
 * {"type":"suggest","attributes":["Author.name", "Book.title"]}
 *
 * // list all authors with name starting with A (see StringQuery)
 * {"type":"suggest","attributes":["Author.name"],"query":"Author.name LIKE 'A%'"}
 *
 * // list all author names and book titles with different queries (see StringQuery)
 * {"type":"suggest","attributes":["Author.name", "Book.title"],"query":{"Author":"Author.name LIKE 'A%'","Book":"Book.title LIKE 'A%'"}}
 *
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SuggestListStrategy implements ListStrategy {

  const CACHE_SECTION = 'suggestliststrategy';

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
   * $options is an associative array with keys 'attributes' and 'query' (optional)
   */
  public function getList($options, $valuePattern=null, $key=null, $language=null) {
    if (!isset($options['attributes'])) {
      throw new ConfigurationException("No 'attributes' given in list options: ".StringUtil::getDump($options));
    }
    $localization = ObjectFactory::getInstance('localization');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $attributes = $options['attributes'];

    $hasQuery = (isset($options['query']) && !empty($options['query'])) || $valuePattern;
    $needsTranslation = $language != null && $language != $localization->getDefaultLanguage();

    // set cache id for unfiltered result
    $cacheId = !$hasQuery ? hash('sha256', join('+', [json_encode($options), $valuePattern, $language])) : null;

    $list = [];
    if ($cacheId && $this->cache->exists(self::CACHE_SECTION, $cacheId)) {
      $list = $this->cache->get(self::CACHE_SECTION, $cacheId);
    }
    else {
      foreach ($attributes as $attribute) {
        list($typeName, $attributeName) = $this->getTypeAndAttribute($attribute);
        $query = new StringQuery($typeName);
        if ($hasQuery) {
          $queryValue = $options['query'];
          if (is_string($queryValue)) {
            $query->setConditionString($queryValue);
          }
          else if (isset($queryValue[$typeName])) {
            $query->setConditionString($queryValue[$typeName]);
          }
        }
        // add display value query
        if ($valuePattern) {
          $dbPattern = preg_replace('/^\/|\/$/', '', $valuePattern);
          $valuePatterns = [];
          $mapper = $persistenceFacade->getMapper($typeName);
          foreach ($mapper->getProperties()['displayValues'] as $displayValue) {
            $valuePatterns[] = $typeName.'.'.$displayValue.' REGEXP '.$mapper->quoteValue($dbPattern);
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
          $value = $object->getValue($attributeName);
          if (strlen($value) > 0) {
            $list[$value] = $value;
          }
        }
      }
      natsort($list);
      $list = array_unique($list);
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
   * Derive type and attribute from a string of the form type.attribute
   * @param mixed $typeAttr
   * @return array of type name and attribute name
   */
  protected function getTypeAndAttribute($typeAttr) {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $parts = explode('.', $typeAttr);
    $attribute = array_pop($parts);
    $type = join('.', $parts);
    if ($persistenceFacade->isKnownType($type)) {
      $mapper = $persistenceFacade->getMapper($type);
      if ($mapper->hasAttribute($attribute)) {
        return [$type, $attribute];
      }
    }
    throw new ConfigurationException("Could not resolve \"{$typeAttr}\"");
  }
}
?>
