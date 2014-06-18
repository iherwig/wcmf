<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
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
    $this->_hits = $search->find($queryCondition, $pagingInfo);

    $oids = array();
    foreach ($this->_hits as $hit) {
      $oids[] = ObjectId::parse($hit['oid']);
    }

    // load the objects
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $objects = array();
    foreach($oids as $oid) {
      if ($permissionManager->authorize($oid, '', PersistenceAction::READ)) {
        $obj = $persistenceFacade->load($oid);
        $objects[] = $obj;
      }
    }
    return $objects;
  }

  /**
   * @see ListController::modifyModel()
   */
  protected function modifyModel($nodes) {
    parent::modifyModel($nodes);

    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    for ($i=0, $count=sizeof($nodes); $i<$count; $i++) {
      $curNode = &$nodes[$i];
      $hit = $this->_hits[$curNode->getOID()->__toString()];
      $curNode->setValue('displayValue', $curNode->getDisplayValue());
      $curNode->setValue('summary', "... ".$hit['summary']." ...");
      $curNode->setValue('type', $persistenceFacade->getSimpleType($curNode->getType()));
    }
  }
}
?>
