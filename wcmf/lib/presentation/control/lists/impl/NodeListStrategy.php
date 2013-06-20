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

use wcmf\lib\core\ObjectFactory;
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
   * @see ListStrategy::getListMap
   */
  public function getListMap($configuration, $language=null) {
    $result = array();
    // TODO: implement node fetching
    return $result;
  }
}
?>
