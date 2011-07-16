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

/**
 * @class ArrayUtil
 * @ingroup Util
 * @brief ArrayUtil provides support for array manipulation.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ArrayUtil
{
  /*
   * Implementation of in_array case insensitive search (see PHP documentation for parameters)
   */
  public static function in_array_i($str, $array)
  {
    return (sizeof(ArrayUtil::get_matching_values_i($str, $array)) > 0);
  }
  /*
   * Get array values that match a string case insensitive.
   * @param str String to match
   * @param array Array to search in
   * @return Array of matched values
   */
  public static function get_matching_values_i($str, $array)
  {
    return preg_grep('/^' . preg_quote($str, '/') . '$/i', $array);
  }
  /*
   * Implementation of array_slice for assoziative arrays (see PHP documentation for parameters)
   */
  public static function key_array_splice(&$input, $key_ofs, $length=null, $replacement=null)
  {
    // Adjust the length if it was negative or not passed
    if ($length === null || $length < 0)
      $count = $length + count($input);

    // Cycle through the array
    foreach ($input as $key => $value)
    {
      if (!$key_found)
      {
        if ($key === $key_ofs)
        {
          $key_found = true;
          if ($length !== null && $length >= 0)
            $count = $length;
          if (is_array($replacement))
            foreach($replacement as $r_key => $r_value)
              $new_array[$r_key] = $r_value;
        }
        else
          $new_array[$key] = $value;
      }
      if ($key_found)
      {
        if ($count > 0)
          $ret_array[$key] = $value;
        else
          $new_array[$key] = $value;
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
  public static function key_array_rename(&$input, $oldname, $newname)
  {
    ArrayUtil::key_array_splice($input, $oldname, 1,
      array($newname => $input[$oldname]));
    unset($input[$oldname]);
  }

  /*
   * Insert a value into an array at arbitrary position
   * @param input A reference to the array to insert to
   * @param pos The position (0 < pos < sizeof(input))
   * @param val A reference to the value to insert
   * @param unique True/False indicating wether the value should be ignored if it
   * already exists or not.
   * @return True/False indicating wether the value was inserted or not
   */
  public static function array_insert(&$input, $pos, &$val, $unique=true)
  {
    if (!is_array($input))
  	  $input = array();
    if ($unique && in_array($val, $input, true))
      return false;
    array_splice($input, $pos, 0, array(&$val));
    return true;
  }

  /*
   * Remove a value from an array
   * @param input A reference to the array to remove from
   * @param val A reference to the value to remove
   */
  public static function array_remove(&$input, &$val)
  {
  	$input = array_diff($input, array($val));
  }

  /**
   * Object Id array helper methods
   */

  /*
   * Remove duplicated entries from an array of objects
   * @param objArray The array of objects
   * @return An array of objects made unique by oid
   */
  public static function array_unique_by_oid($objArray)
  {
    $out = array();
    $oidList = array();
    for($i=0; $i<sizeof($objArray); $i++)
    {
      $curObj = &$objArray[$i];
      if(!in_array($curObj->getOID(), $oidList, true))
      {
        $oidList[sizeof($oidList)] = $curObj->getOID();
        $out[sizeof($out)] = &$curObj;
      }
    }
    return $out;
  }

  /**
   * Get the previous and the next object id from an object list,
   * based on the given oid. If wrap is false, null is returned
   * for prevOID if oid is at the beginning of the list and for
   * the end respectively. The list is wrapped at beginning and
   * end, if wrap is true.
   * @param oid The marker object id
   * @param objList A reference to the object list
   * @param wrap True/False wether to wrap the list or not
   * @return An Array with two the previous and the next oid
   */
  public static function getPrevNextOIDs($oid, &$objList, $wrap)
  {
    $prevOID = null;
    $nextOID = null;
    $listSize = sizeof($objList);

    if ($listSize > 0)
    {
      for ($i=0; $i<$listSize; $i++)
      {
        if ($objList[$i]->getOID() == $oid)
        {
          // determine previous
          if ($i == 0)
          {
            if ($wrap)
              $prevOID = $objList[$listSize-1]->getOID();
            else
              $prevOID = null;
          }
          else
            $prevOID = $objList[$i-1]->getOID();

          // determine next
          if ($i == $listSize-1)
          {
            if ($wrap)
              $nextOID = $objList[0]->getOID();
            else
              $nextOID = null;
          }
          else
            $nextOID = $objList[$i+1]->getOID();
        }
      }
    }
    return array($prevOID, $nextOID);
  }
}
?>
