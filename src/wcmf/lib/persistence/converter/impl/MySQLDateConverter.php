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
namespace wcmf\lib\persistence\converter\impl;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\converter\DataConverter;

/**
 * MySQLDateConverter converts MySQL dates to a date localized to the users or
 * application settings.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class MySQLDateConverter implements DataConverter
{
  /**
   * @see DataConverter::convertStorageToApplication()
   */
  function convertStorageToApplication($data, $type, $name)
  {
    $type = strtolower($type);
    if ($data != '' && ($type == 'datetime' || $type == 'date'))
    {
      $locale = strtolower(ObjectFactory::getInstance('localization')->getUILanguage());

      // handle empty dates
      if (strpos($data, "0000-00-00") === 0)
        return "";

        // convert localized date/time to language format
        // we don't rely on setLocale, strftime and strtotime
        $convertFunction = "storageTo_".$locale;
        $methods = get_class_methods($this);
        if (in_array($convertFunction, $methods))
          $data = $this->$convertFunction($data, $type);
    }
    return $data;
  }
  /**
   * @see DataConverter::convertApplicationToStorage()
   * This method uses methods named localeToEnglish (e.g. de_deToEnglish) to
   * convert a localized date/time string back to english format. Localization
   * was done using strftime("%x %X", time) resp. strftime("%x", time).
   * To support a special locale the appropriate method must be implemented.
   */
  function convertApplicationToStorage($data, $type, $name)
  {
    if ($data != '')
    {
      $locale = strtolower(ObjectFactory::getInstance('localization')->getUILanguage());
      $type = strtolower($type);

      if ($type == 'datetime' || $type == 'date')
      {
        // convert localized date/time to english format
        $convertFunction = $locale."ToEnglish";
        $methods = get_class_methods($this);
        if (in_array($convertFunction, $methods))
          $date = $this->$convertFunction($data, $type);

        // convert date/time to mysql
        if ($type == 'datetime')
          $data = strftime("%Y-%m-%d %H:%M:%S", strtotime($date));
        if ($type == 'date')
          $data = strftime("%Y-%m-%d", strtotime($date));
      }
    }
    return $data;
  }
  /**
   * Convert a german date/time string to english format
   * @param date The localized date/time
   * @param type One of these: date or datetime
   */
  function de_deToEnglish($date, $type)
  {
    // test german date format, return original if not matching
    $testFormat = preg_split("/[\.: ]/", $date);
    if (sizeof($testFormat) != 6 && sizeof($testFormat) != 5 && sizeof($testFormat) != 3)
      return $date;

    list($d, $m, $Y, $H, $M, $S) = preg_split("/[\.: ]/", $date);
    if ($type == 'datetime')
    {
      // make sure we get a valid date format even if only a date is supplied with the datetime type
      if ($H=="") $H = "00";
      if ($M=="") $M = "00";
      if ($S=="") $S = "00";
      return $m."/".$d."/".$Y." ".$H.":".$M.":".$S;
    }
    elseif ($type == 'date')
      return $m."/".$d."/".$Y;
    else
      return $date;
  }
  /**
   * Convert a english date/time string to english format
   * @param date The localized date/time
   * @param type One of these: date or datetime
   */
  function en_enToEnglish($date, $type)
  {
      return $date;
  }
  /**
   * Convert a storage date format to german date/time string
   * @param date The date/time in storage format
   * @param type One of these: date or datetime
   */
  function storageTo_de_de($date, $type)
  {
    if ($type == 'datetime')
      return strftime("%d.%m.%Y %H:%M:%S", strtotime($date));
    elseif ($type == 'date')
      return strftime("%d.%m.%Y", strtotime($date));
    else
      return $date;
  }
  /**
   * Convert a storage date format to english date/time string
   * @param date The date/time in storage format
   * @param type One of these: date or datetime
   */
  function storageTo_en_en($date, $type)
  {
    if ($type == 'datetime')
      return strftime("%m/%d/%Y %H:%M:%S", strtotime($date));
    elseif ($type == 'date')
      return strftime("%m/%d/%Y", strtotime($date));
    else
      return $date;
  }
}
?>
