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
namespace wcmf\application\controller;

use wcmf\application\controller\ListController;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceAction;

/**
 *  * @class SearchController
 * @ingroup Controller
 * @brief SearchController is a controller that executes a search for oids and
 * displays them in a paged list. Internally it uses Zend Lucene indexed search.
 *
 * <b>Input actions:</b>
 * - unspecified: Search
 *
 * <b>Output actions:</b>
 * - @em ok In any case
 *
 * @param [in,out] query The query
 * see ListController for additional parameters
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SearchController extends ListController {

  protected $_hits = array();

  /**
   * @see ListController::getObjects()
   */
  protected function getObjects($type, $queryCondition, $sortArray, $pagingInfo) {
    $permissionManager = ObjectFactory::getInstance('permissionManager');

    // search with searchterm (even if empty) if no query is given
    $search = ObjectFactory::getInstance('search');
    $this->_hits = $search->find($queryCondition);

    $allOIDs = array();
    foreach ($this->_hits as $hit) {
      $allOIDs[] = ObjectId::parse($hit['oid']);
    }
    $allOIDs = array_unique($allOIDs);
    $oids = $allOIDs;

    // update pagingInfo
    if ($pagingInfo) {
      $pagingInfo->setTotalCount(sizeof($allOIDs));

      // select the requested slice
      if ($pagingInfo->getPageSize() == -1) {
        $size = $pagingInfo->getTotalCount();
      }
      else {
        $size = $pagingInfo->getPageSize();
      }
      $oids = array_slice($allOIDs, ($pagingInfo->getPage()-1)*$size, $size);
    }

    // load the objects
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $objects = array();
    foreach($oids as $oid) {
      if ($permissionManager->authorize($oid, '', PersistenceAction::READ)) {
        $obj = $persistenceFacade->load($oid);
        $objects[] = &$obj;
      }
    }
    return $objects;
  }

  /**
   * @see ListController::modifyModel()
   */
  protected function modifyModel($nodes) {
    parent::modifyModel($nodes);

    for ($i=0, $count=sizeof($nodes); $i<$count; $i++) {
      $curNode = &$nodes[$i];
      $hit = $this->_hits[$curNode->getOID()->__toString()];
      $curNode->setValue('summary', $curNode->getDisplayValue()."<hr>... ".$hit['summary']." ...");

      $curNode->setValue('type', $curNode->getType());
      $curNode->setValue('displayValue', $curNode->getDisplayValue());
    }
  }
}
?>
