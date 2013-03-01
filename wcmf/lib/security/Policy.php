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
namespace wcmf\lib\security;

/**
 * Permission policy
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Policy {

  /**
   * Parse an policy string and returns an associative array with the keys
   * ('default', 'allow', 'deny'). Where 'allow', 'deny' are arrays itselves holding roles.
   * 'deny' overwrites 'allow' overwrites 'default'
   * @param val An role string (+*, +admin, -guest, entries without '+' or '-'
   *     prefix default to allow rules).
   * @return An array containing the policy data as an associative array with the keys
   *     'default', 'allow', 'deny'.
   */
  public static function parse($val) {
    $rtn = array();

    $roles = explode(" ", $val);
    foreach ($roles as $value) {
      $value=trim($value);
      if (strlen($value)==2 && substr($value,1,1) == "*") {
        if (substr($value,0,1)=="+") {
          $rtn['default'] = true;
        }
        else if (substr($value,0,1)=="-") {
          $rtn['default'] = false;
        }
      }
      else {
        if (substr($value,0,1)=="+") {
          $rtn['allow'][] = substr($value,1);
        }
        else if (substr($value,0,1)=="-") {
          $rtn['deny'][] = substr($value,1);
        }
        else {
          // entries without '+' or '-' prefix default to allow rules
          $rtn['allow'][] = $value;
        }
      }
    }
    return $rtn;
  }
}
?>
