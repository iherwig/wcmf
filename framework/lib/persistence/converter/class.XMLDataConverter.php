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
require_once(WCMF_BASE."wcmf/lib/util/class.Message.php");
require_once(WCMF_BASE."wcmf/lib/persistence/converter/class.IDataConverter.php");

/**
 * @class XMLDataConverter
 * @ingroup Converter
 * @brief XMLDataConverter converts data between storage and XML files.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class XMLDataConverter implements IDataConverter
{
  /**
   * @see DataConverter::convertStorageToApplication()
   */
  function convertStorageToApplication($data, $type, $name)
  {
    if ($type == 'data_date' && $data != '')
    {
      list($year, $month, $day) = preg_split('/-/', $data);
      $data = date("d.m.Y", mktime(3, 0, 0, $month, $day, $year));
    }
    // convert htmlspecialchars that where stored in the db
    $trans = get_html_translation_table(HTML_ENTITIES);
    $trans = array_flip($trans);
    $data = strtr(stripslashes(stripslashes($data)), $trans);

    return $data;
  }
  /**
   * @see DataConverter::convertApplicationToStorage()
   */
  function convertApplicationToStorage($data, $type, $name)
  {
    if ($type == 'data_date' && $data != '')
    {
      list($day, $month, $year) = preg_split('/\./', $data);
      $data = date("Y-m-d", mktime(3, 0, 0, $month, $day, $year));
    }

    return htmlspecialchars($data);
  }
}
?>
