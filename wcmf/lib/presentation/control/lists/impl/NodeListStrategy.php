<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2009 wemove digital solutions GmbH
 *
 * Licensed under the terms of any of the following licenses
 * at your choice:
 *
 * - GNU Lesser General Public License (LGPL)
 *   http://www.gnu.org/licenses/lgpl.html
 * - Eclipse Public License (EPL)
 *   http://www.eclipse.org/org/documents/epl-v10.php
 *
 * See the license.txt file distributed with this work for
 * additional information.
 *
 * $Id$
 */
namespace wcmf\lib\presentation\control\lists\impl;

use wcmf\lib\model\StringQuery;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\presentation\control\lists\ListStrategy;

/**
 * NodeListStrategy implements a list of entities that is retrieved
 * from the store, where the keys are the object ids and the
 * values are the display values.
 * The following list definition(s) must be used in the input_type configuraton:
 * @code
 * node:type // list with all entities of the given type
 * node:type1,type2,... // list with all entities of the given types
 *
 * node:type|type.name LIKE 'A%' ... // list with all entities of the given type that
 *                                      match the given query (@see StringQuery)
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NodeListStrategy implements ListStrategy {

  /**
   * @see ListStrategy::getList
   */
  public function getList($configuration, $language=null) {

    $listConfig = $this->parseConfiguration($configuration);

    $list = array();
    foreach ($listConfig['types'] as $type) {
      $query = new StringQuery($type);
      if ($listConfig['query']) {
        $query->setConditionString($listConfig['query']);
      }
      $objects = $query->execute(BuildDepth::SINGLE);
      foreach ($objects as $object) {
        $list[$object->getOID()->__toString()] = $object->getDisplayValue();
      }
    }

    return $list;
  }

  /**
   * Parse the given list configuration
   * @param configuration The configuration
   * @return Associative array with keys 'types' and 'query'
   */
  protected function parseConfiguration($configuration) {
    $query = null;
    if (strPos($configuration, '|')) {
      list($typeDef, $query) = explode('|', $configuration, 2);
    }
    else {
      $typeDef = $configuration;
    }
    $types = explode(',', $typeDef);

    return array(
      'types' => $types,
      'query' => $query
    );
  }

  /**
   * @see ListStrategy::isStatic
   */
  public function isStatic($configuration) {
    return false;
  }
}
?>
