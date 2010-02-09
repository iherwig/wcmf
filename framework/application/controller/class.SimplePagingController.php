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
require_once(BASE."wcmf/application/controller/class.PagingController.php");
require_once(BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(BASE."wcmf/lib/util/class.InifileParser.php");
require_once(BASE."wcmf/lib/util/class.StringUtil.php");

/**
 * @class SimplePagingController
 * @ingroup Controller
 * @brief SimplePagingController is a controller demonstrating the use
 * of PagingController for splitting long lists into several pages.
 * 
 * <b>Input actions:</b>
 * - see PagingController
 *
 * <b>Output actions:</b>
 * - see PagingController
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SimplePagingController extends PagingController
{
  /**
   * @see PagingController::getOIDs()
   */
  function getOIDs()
  {
    // get all known types from configuration file
    $parser = &InifileParser::getInstance();
    $types = array_keys($parser->getSection('typemapping'));
    
    // get object ids from all types
    $persistenceFacade = &PersistenceFacade::getInstance();
    $oids = array();
    foreach ($types as $type)
      $oids = array_merge($oids, $persistenceFacade->getOIDs($type));

    return $oids;
  }
  /**
   * @see PagingController::getDisplayText()
   */
  function getDisplayText(&$node)
  {
    return strip_tags(preg_replace("/[\r\n']/", " ", NodeUtil::getDisplayValue($node)));
  }
}
?>
