<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
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
   * @param $val An role string (+*, +admin, -guest, entries without '+' or '-'
   *     prefix default to allow rules).
   * @return An array containing the policy data as an associative array with the keys
   *     'default', 'allow', 'deny'.
   */
  public static function parse($val) {
    $result = array();

    $roleValues = explode(" ", $val);
    foreach ($roleValues as $roleValue) {
      $roleValue = trim($roleValue);
      $matches = array();
      preg_match('/^([+-]?)(.+)$/', $roleValue, $matches);
      $prefix = $matches[1];
      $role = $matches[2];
      if ($role === '*') {
        $result['default'] = $prefix == '-' ? false : true;
      }
      else {
        if ($prefix === '-') {
          $result['deny'][] = $role;
        }
        else {
          // entries without '+' or '-' prefix default to allow rules
          $result['allow'][] = $role;
        }
      }
    }
    return $result;
  }
}
?>
