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
namespace wcmf\lib\presentation\control\lists\impl;

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
    $localization = ObjectFactory::getInstance('localization');

    $list = [];
    foreach ($types as $type) {
      $query = new StringQuery($type);
      if (isset($options['query'])) {
        $query->setConditionString($options['query']);
      }
      $objects = $query->execute(BuildDepth::SINGLE);
      foreach ($objects as $object) {
        $object = $localization->loadTranslation($object, $language);
        $id = $isSingleType ? $object->getOID()->getFirstId() : $object->getOID()->__toString();
        $list[$id] = $object->getDisplayValue();
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
