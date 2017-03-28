<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2017 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\application\controller;

use wcmf\application\controller\ListController;
use wcmf\lib\model\NodeComparator;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\PersistenceAction;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;

/**
 * HistoryController returns a list of last changed entity instances.
 *
 * The controller supports the following actions:
 *
 * <div class="controller-action">
 * <div> __Action__ _default_ </div>
 * <div>
 * Search.
 * | Parameter              | Description
 * |------------------------|-------------------------
 * | __Response Actions__   | |
 * | `ok`                   | In all cases
 * </div>
 * </div>
 *
 * For additional actions and parameters see [ListController actions](@ref ListController).
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class HistoryController extends ListController {

  /**
   * @see Controller::initialize()
   */
  public function initialize(Request $request, Response $response) {
    $request->setValue('completeObjects', true);
    parent::initialize($request, $response);
  }

  /**
   * @see Controller::validate()
   */
  protected function validate() {
    // skip validation
    return true;
  }

  /**
   * @see ListController::getObjects()
   */
  protected function getObjects($type, $queryCondition, $sortArray, $pagingInfo) {
    $permissionManager = $this->getPermissionManager();
    $persistenceFacade = $this->getPersistenceFacade();

    // find types with attributes created, creator, modified, last_editor
    $requiredAttributes = ['created', 'creator', 'modified', 'last_editor'];
    $types = [];
    foreach ($persistenceFacade->getKnownTypes() as $type) {
      $mapper = $persistenceFacade->getMapper($type);
      $matches = true;
      foreach ($requiredAttributes as $attribute) {
        if (!$mapper->hasAttribute($attribute)) {
          $matches = false;
          break;
        }
      }
      if ($matches) {
        $types[] = $type;
      }
    }

    // load objects
    // NOTE: we always get last changed objects and sort by requested value later
    $historyItems = $persistenceFacade->loadObjects($types, BuildDepth::SINGLE,
            null, ['modified DESC'], $pagingInfo);

    $objects = [];
    foreach($historyItems as $historyItem) {
      if ($permissionManager->authorize($historyItem->getOID(), '', PersistenceAction::READ)) {
        $objects[] = $historyItem;
      }
    }
    $pagingInfo->setTotalCount(sizeof($objects));
    return $objects;
  }

  /**
   * @see ListController::modifyModel()
   */
  protected function modifyModel(&$nodes) {
    parent::modifyModel($nodes);

    // add search related values
    $persistenceFacade = $this->getPersistenceFacade();
    for ($i=0, $count=sizeof($nodes); $i<$count; $i++) {
      $curNode = $nodes[$i];
      $hit = $this->hits[$curNode->getOID()->__toString()];
      $curNode->setValue('_displayValue', $curNode->getDisplayValue(), true);
      $curNode->setValue('_summary', "... ".$hit['summary']." ...", true);
      $curNode->setValue('_type', $persistenceFacade->getSimpleType($curNode->getType()), true);
    }

    // sort
    $request = $this->getRequest();
    if ($request->hasValue('sortFieldName')) {
      $sortDir = $request->hasValue('sortDirection') ? $request->getValue('sortDirection') : 'asc';
      $sortCriteria = [
         $request->getValue('sortFieldName') => $sortDir == 'asc' ?
              NodeComparator::SORTTYPE_ASC : NodeComparator::SORTTYPE_DESC
      ];
      $comparator = new NodeComparator($sortCriteria);
      usort($nodes, [$comparator, 'compare']);
    }
  }
}
?>
