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
namespace wcmf\lib\util;

/**
 * ArrayUtil provides support for array manipulation.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ArrayUtil {

  /*
   * Implementation of array_slice for assoziative arrays (see PHP documentation for parameters)
   */
  public static function key_array_splice(&$input, $key_ofs, $length=null, $replacement=null) {
    // Adjust the length if it was negative or not passed
    if ($length === null || $length < 0)
      $count = $length + count($input);

    // Cycle through the array
    foreach ($input as $key => $value) {
      if (!$key_found) {
        if ($key === $key_ofs) {
          $key_found = true;
          if ($length !== null && $length >= 0) {
            $count = $length;
          }
          if (is_array($replacement)) {
            foreach($replacement as $r_key => $r_value) {
              $new_array[$r_key] = $r_value;
            }
          }
        }
        else {
          $new_array[$key] = $value;
        }
      }
      if ($key_found) {
        if ($count > 0) {
          $ret_array[$key] = $value;
        }
        else {
          $new_array[$key] = $value;
        }
      }
      $count--;
    }

    // Finish up
    $input = $new_array;
    return $ret_array;
  }

  /*
   * Rename a key in an assoziative array
   * @param input A reference to the array to rename the key in
   * @param oldname The old name of the key
   * @param newname The new name of the key
   */
  public static function key_array_rename(&$input, $oldname, $newname) {
    self::key_array_splice($input, $oldname, 1, array($newname => $input[$oldname]));
    unset($input[$oldname]);
  }
}
?>
