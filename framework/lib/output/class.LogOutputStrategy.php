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
require_once(WCMF_BASE."wcmf/lib/util/class.Log.php");
require_once(WCMF_BASE."wcmf/lib/output/class.OutputStrategy.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/security/class.RightsManager.php");
/**
 * @class LogOutputStrategy
 * @ingroup Output
 * @brief This OutputStrategy outputs an object's content to the logger category
 * LogOutputStrategy, loglevel info
 * Used classes must implement the toString() method.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class LogOutputStrategy implements OutputStrategy
{
  /**
   * @see OutputStrategy::writeHeader
   */
  public function writeHeader()
  {
    // do nothing
  }
  /**
   * @see OutputStrategy::writeFooter
   */
  public function writeFooter()
  {
    // do nothing
  }
  /**
   * @see OutputStrategy::writeObject
   */
  public function writeObject($obj)
  {
    $persistenceFacade = PersistenceFacade::getInstance();
    $rightsManager = RightsManager::getInstance();
    $user = $rightsManager->getAuthUser();
    switch ($state = $obj->getState())
    {
      // log insert action
      case PersistentObject::STATE_NEW:
        Log::info('INSERT '.$obj->getOID().': '.str_replace("\n", " ", $obj->toString()).' USER: '.$user->getLogin(), __CLASS__);
        break;
      // log update action
      case PersistentObject::STATE_DIRTY:
        // get old object from storage
        $oldObj = $persistenceFacade->load($obj->getOID(), BUILDDEPTH_SINGLE);
        // collect differences
        $values = array();
        $valueNames = $obj->getValueNames();
        foreach($valueNames as $name)
        {
          $values[$name]['name'] = $name;
          $values[$name]['new'] = $obj->getValue($name);
          $values[$name]['old'] = $oldObj->getValue($name);
        }
        // make diff string
        $diff = '';
        foreach ($values as $value)
        {
          if ($value['old'] != $value['new']) {
            $diff .= $value['name'].':'.$value['old'].'->'.$value['new'].' ';
          }
        }
        Log::info('SAVE '.$obj->getOID().': '.$diff.' USER: '.$user->getLogin(), __CLASS__);
        break;
      // log delete action
      case PersistentObject::STATE_DELETED:
        // get old object from storage
        Log::info('DELETE '.$obj->getOID().': '.str_replace("\n", " ", $obj->toString()).' USER: '.$user->getLogin(), __CLASS__);
        break;
    }
  }
}
?>
