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
namespace wcmf\lib\persistence\converter;

/**
 * The default dataconverter.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultDataConverter implements IDataConverter
{
  /**
   * @see DataConverter::convertStorageToApplication()
   */
  function convertStorageToApplication($data, $type, $name)
  {
    // get translation table for htmlspecialchars that where stored in the db
    $trans = get_html_translation_table(HTML_ENTITIES);
    $trans = array_flip($trans);
    $data = nl2br($data);
    return strtr(stripslashes(stripslashes($data)), $trans);
  }
  /**
   * @see DataConverter::convertApplicationToStorage()
   */
  function convertApplicationToStorage($data, $type, $name)
  {
    $data = nl2br($data);
    return htmlspecialchars($data, ENT_QUOTES);
  }
}
?>
