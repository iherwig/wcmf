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
require_once(WCMF_BASE."wcmf/application/controller/class.BatchController.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/util/class.SearchUtil.php");

/**
 * @class SearchIndexController
 * @ingroup Controller
 * @brief SearchIndexController creates a Lucene index from the complete datastore.
 *
 * <b>Input actions:</b>
 * - unspecified: Create the index
 *
 * <b>Output actions:</b>
 * - none
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SearchIndexController extends BatchController
{

  /**
   * @see BatchController::getWorkPackage()
   */
  function getWorkPackage($number)
  {
    if ($number == 0)
    {
      // get all types to index
      $types = array();
      $persistenceFacade = &PersistenceFacade::getInstance();
      foreach (PersistenceFacade::getKnownTypes() as $type)
      {
        $tpl = &$persistenceFacade->create($type, BUILDDEPTH_SINGLE);
        if ($tpl->isIndexInSearch()) {
          array_push($types, $type);
        }
      }
      
      SearchUtil::resetIndex();

      return array('name' => Message::get('Collect objects'), 'size' => 1, 'oids' => $types, 'callback' => 'collect');
    }
    else {
      return null;
    }
  }
  /**
   * Collect all oids of the given types
   * @param types The types to process
   * @note This is a callback method called on a matching work package @see BatchController::addWorkPackage()
   */
  function collect($types)
  {
    $persistenceFacade = &PersistenceFacade::getInstance();
    foreach ($types as $type)
    {
      $oids = $persistenceFacade->getOIDs($type);
      if (sizeof($oids) == 0) {
        $oids = array(1);
      }
      $this->addWorkPackage(Message::get('Indexing %1%', array($type)), 10, $oids, 'index');
    }
  }
  /**
   * Create the lucene index from the given objects
   * @param oids The oids to process
   * @note This is a callback method called on a matching work package @see BatchController::addWorkPackage()
   */
  function index($oids)
  {
    $persistenceFacade = &PersistenceFacade::getInstance();
    foreach($oids as $oid)
    {
      if (PersistenceFacade::isValidOID($oid))
      {
        $obj = &$persistenceFacade->load($oid, BUILDDEPTH_SINGLE);
        $obj->indexInSearch();
      }
    }

    $index = SearchUtil::getIndex();
    $index->commit();
    
    if ($this->getStepNumber() == $this->getNumberOfSteps() - 1) {
      $this->addWorkPackage(Message::get('Optimizing index'), 1, array(0), 'optimize');
    }
  }

  function optimize($oids)
  {
    $index = SearchUtil::getIndex();
    $index->optimize();
  }
  // PROTECTED REGION END
}
?>
