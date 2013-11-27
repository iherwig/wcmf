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
namespace wcmf\application\controller\admintool;

use wcmf\application\controller\BatchController;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\i18n\Message;
use wcmf\lib\search\IndexedSearch;

/**
 * SearchIndexController creates a Lucene index from the complete datastore.
 *
 * <b>Input actions:</b>
 * - unspecified: Create the index
 *
 * <b>Output actions:</b>
 * - none
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SearchIndexController extends BatchController {

  /**
   * @see BatchController::getWorkPackage()
   */
  protected function getWorkPackage($number) {
    if ($number == 0) {
      $search = ObjectFactory::getInstance('search');
      if ($search instanceof IndexedSearch) {
        // get all types to index
        $types = array();
        $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
        foreach ($persistenceFacade->getKnownTypes() as $type) {
          $tpl = $persistenceFacade->create($type, BuildDepth::SINGLE);
          if ($search->isSearchable($tpl)) {
            array_push($types, $type);
          }
        }
        $search->resetIndex();
        return array('name' => Message::get('Collect objects'), 'size' => 1, 'oids' => $types, 'callback' => 'collect');
      }
      else {
        // no index to be updated
        return null;
      }
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
  protected function collect($types) {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    foreach ($types as $type) {
      $oids = $persistenceFacade->getOIDs($type);
      if (sizeof($oids) == 0) {
        $oids = array(1);
      }
      $this->addWorkPackage(Message::get('Indexing %0%', array($type)), 10, $oids, 'index');
    }
  }

  /**
   * Create the lucene index from the given objects
   * @param oids The oids to process
   * @note This is a callback method called on a matching work package @see BatchController::addWorkPackage()
   */
  protected function index($oids) {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $search = ObjectFactory::getInstance('search');
    foreach($oids as $oid) {
      if (ObjectId::isValidOID($oid)) {
        $obj = $persistenceFacade->load($oid, BuildDepth::SINGLE);
        $search->addToIndex($obj);
      }
    }
    $search->commitIndex(false);

    if ($this->getStepNumber() == $this->getNumberOfSteps() - 1) {
      $this->addWorkPackage(Message::get('Optimizing index'), 1, array(0), 'optimize');
    }
  }

  function optimize($oids) {
    $search = ObjectFactory::getInstance('search');
    $search->optimizeIndex();
  }
  // PROTECTED REGION END
}
?>
