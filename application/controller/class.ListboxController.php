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
require_once(BASE."wcmf/lib/presentation/class.Controller.php");
require_once(BASE."wcmf/lib/util/class.Obfuscator.php");

/**
 * @class ListboxController
 * @ingroup Controller
 * @brief ListboxController is a controller that uses g_getOIDs to retrieve listbox data.
 * 
 * <b>Input actions:</b>
 * - unspecified: List Nodes of given type
 *
 * <b>Output actions:</b>
 * - @em ok In any case
 * 
 * @param[in] type The entity type to list
 * @param[in] filter A query passed to g_getOIDs
 * @param[in] displayFilter A regular expression that the returned 'val' values should match
 * @param[out] totalCount The total number of all entities that match the criteria
 * @param[out] objects An associative array with keys 'key' and 'val'
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ListboxController extends Controller
{
  /**
   * @see Controller::hasView()
   */
  function hasView()
  {
    return false;
  }
  /**
   * Do processing and assign Node data to View.
   * @return False in every case.
   * @see Controller::executeKernel()
   */
  function executeKernel()
  {
    // unveil the filter value if it is ofuscated
    $filter = $this->_request->getValue('filter');
    $unveiled = Obfuscator::unveil($filter);
    if (strlen($filter) > 0) 
    { 
      if (strlen($unveiled) > 0) {
        $filter = $unveiled;
      }
      else {
        $filter = stripslashes($filter);
      }
    }

    $objects = g_getOIDs($this->_request->getValue('type'), $filter);
    if ($this->isLocalizedRequest()) {
      $objects = g_getOIDs($this->_request->getValue('type'), $filter, null, false, 
	      $this->_request->getValue('language'));
	  }
    else {
      $objects = g_getOIDs($this->_request->getValue('type'), $filter);
    }

    // translate all nodes to the requested language if requested
    if ($this->isLocalizedRequest())
    {
      $localization = Localization::getInstance();
      for ($i=0; $i<sizeof($objects); $i++) {
        $localization->loadTranslation($objects[$i], $this->_request->getValue('language'), true, true);
      }
    }
    
    // apply displayFilter, if given
    $regexp = $this->_request->getValue('displayFilter');
    if (strlen($regexp) > 0)
    {
      $regexp = '/'.$regexp.'/i';
      $tmp = array();
      foreach ($objects as $key => $val)
      {
        if (preg_match($regexp, $val)) {
          $tmp[$key] = $val;
        }
      }
      $objects = $tmp;
    }
    
    $this->_response->setValue('totalCount', sizeof($objects));
    $responseObjects = array();
    foreach($objects as $key => $val)
      array_push($responseObjects, array('key' => $key, 'val' => $val));
    $this->_response->setValue('objects', $responseObjects);
    
    // success
    $this->_response->setAction('ok');
    return false;
  }
}
?>
