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
namespace wcmf\lib\presentation\control;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\presentation\control\Control;
use wcmf\lib\presentation\control\ListStrategy;

/**
 * FixedListStrategy implements a constant list of key value pairs.
 * The following list definition(s) must be used in the input_type configuraton:
 * @code
 * fix:key1[val1]|key2[val2]|... // list with explicit key value pairs
 *
 * fix:$global_array_variable // list with key value pairs defined in a global variable
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class FixedListStrategy implements ListStrategy {

  /**
   * @see ListStrategy::getListMap
   */
  public function getListMap($configuration, $value=null, $nodeOid=null, $language=null) {
    // see if we have an array variable or a list definition
    if (strPos($configuration, '$') === 0) {
      $entries = $GLOBALS[subStr($configuration, 1)];
    }
    else {
      $entries = preg_split('/\|/', $configuration);
    }
    if (!is_array($entries)) {
      throw new ConfigurationException($configuration." is no array.");
    }
    // process list
    foreach($entries as $curEntry) {
      preg_match_all("/([^\[]*)\[*([^\]]*)\]*/", $curEntry, $matches);
      if (sizeOf($matches) > 0) {
        if ($val2 != '') {
          // value given
          $map[$val1] = $val2;
        }
        else {
          // only key given
          $map[$val1] = $val1;
        }
      }
    }
    return $map;
  }
}
?>
