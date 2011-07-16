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
require_once(WCMF_BASE."wcmf/lib/util/Message.php");
require_once(WCMF_BASE."wcmf/lib/persistence/converter/IDataConverter.php");

/**
 * @class HTMLDataConverter
 * @ingroup Converter
 * @brief HTMLDataConverter converts data between storage and HTML files.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class HTMLDataConverter implements IDataConverter
{
  /**
   * @see DataConverter::convertStorageToApplication()
   */
  function convertStorageToApplication($data, $type, $name)
  {
    $data = preg_replace("/\r\n|\n\r|\n|\r/", "<br />", $data);
    return $data;
  }
  /**
   * @see DataConverter::convertApplicationToStorage()
   */
  function convertApplicationToStorage($data, $type, $name)
  {
    return $data;
  }
}
?>
