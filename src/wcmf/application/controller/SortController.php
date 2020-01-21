<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2020 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\application\controller;

use wcmf\lib\model\Node;
use wcmf\lib\model\NullNode;
use wcmf\lib\model\ObjectQuery;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PagingInfo;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\Controller;

/**
 * SortController is used to change the order of nodes. Nodes can either be
 * sorted in a list of nodes of the same type (_moveBefore_ action) or in a list
 * of child nodes of a container node (_insertBefore_ action).
 *
 * The controller supports the following actions:
 *
 * <div class="controller-action">
 * <div> __Action__ moveBefore </div>
 * <div>
 * Insert an object before a reference object in the list of all objects of the same type.
 * | Parameter              | Description
 * |------------------------|-------------------------
 * | _in_ `insertOid`       | The object id of the object to insert/move
 * | _in_ `referenceOid`    | The object id of the object to insert the inserted object before. If the inserted object should be the last in the container order, the _referenceOid_ contains the special value `ORDER_BOTTOM`
 * | __Response Actions__   | |
 * | `ok`                   | In all cases
 * </div>
 * </div>
 *
 * <div class="controller-action">
 * <div> __Action__ insertBefore </div>
 * <div>
 * Insert an object before a reference object in the order of a container object.
 * | Parameter              | Description
 * |------------------------|-------------------------
 * | _in_ `containerOid`    | The oid of the container object
 * | _in_ `insertOid`       | The oid of the object to insert/move
 * | _in_ `referenceOid`    | The object id of the object to insert the inserted object before. If the inserted object should be the last in the container order, the _referenceOid_ contains the special value `ORDER_BOTTOM`
 * | _in_ `role`            | The role, that the inserted object should have in the container object.
 * | __Response Actions__   | |
 * | `ok`                   | In all cases
 * </div>
 * </div>
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SortController extends Controller {

  const ORDER_BOTTOM = 'ORDER_BOTTOM';
  const UNBOUND = 'UNBOUND';

  /**
   * @see Controller::validate()
   */
  protected function validate() {

    $request = $this->getRequest();
    $response = $this->getResponse();
    $persistenceFacade = $this->getPersistenceFacade();

    $isOrderBottom = $this->isOrderBotton($request);

    // check object id validity
    $insertOid = ObjectId::parse($request->getValue('insertOid'));
    if (!$insertOid) {
      $response->addError(ApplicationError::get('OID_INVALID',
        ['invalidOids' => [$request->getValue('insertOid')]]));
      return false;
    }
    $referenceOid = ObjectId::parse($request->getValue('referenceOid'));
    if (!$referenceOid && !$isOrderBottom) {
      $response->addError(ApplicationError::get('OID_INVALID',
        ['invalidOids' => [$request->getValue('referenceOid')]]));
      return false;
    }

    // action specific

    if ($request->getAction() == 'moveBefore') {
      // check matching classes for move operation
      if (!$isOrderBottom && $insertOid->getType() != $referenceOid->getType()) {
        $response->addError(ApplicationError::get('CLASSES_DO_NOT_MATCH'));
        return false;
      }
      // check if the class supports order
      $mapper = $persistenceFacade->getMapper($insertOid->getType());
      if (!$mapper->isSortable()) {
        $response->addError(ApplicationError::get('ORDER_UNDEFINED'));
        return false;
      }
    }

    if ($request->getAction() == 'insertBefore') {
      // check object id validity
      $containerOid = ObjectId::parse($request->getValue('containerOid'));
      if(!$containerOid) {
        $response->addError(ApplicationError::get('OID_INVALID',
          ['invalidOids' => [$request->getValue('containerOid')]]));
        return false;
      }

      // check association for insert operation
      $mapper = $persistenceFacade->getMapper($containerOid->getType());
      $relationDesc = $mapper->getRelation($request->getValue('role'));
      if (!$relationDesc) {
        $response->addError(ApplicationError::get('ROLE_INVALID'));
        return false;
      }
      // check if object supports order
      $otherMapper = $relationDesc->getOtherMapper();
      if (!$otherMapper->isSortable($relationDesc->getThisRole())) {
        $response->addError(ApplicationError::get('ORDER_NOT_SUPPORTED'));
        return false;
      }
    }
    return true;
  }

  /**
   * @see Controller::doExecute()
   */
  protected function doExecute($method=null) {
    $this->requireTransaction();
    $request = $this->getRequest();
    $response = $this->getResponse();

    // do actions
    if ($request->getAction() == 'moveBefore') {
      $this->doMoveBefore();
    }
    else if ($request->getAction() == 'insertBefore') {
      $this->doInsertBefore();
    }

    $response->setAction('ok');
  }

  /**
   * Execute the moveBefore action
   */
  protected function doMoveBefore() {
    $request = $this->getRequest();
    $persistenceFacade = $this->getPersistenceFacade();
    $isOrderBottom = $this->isOrderBotton($request);

    // load the moved object and the reference object
    $insertOid = ObjectId::parse($request->getValue('insertOid'));
    $referenceOid = ObjectId::parse($request->getValue('referenceOid'));
    $insertObject = $persistenceFacade->load($insertOid);
    $referenceObject = $isOrderBottom ? new NullNode() : $persistenceFacade->load($referenceOid);

    // check object existence
    $objectMap = ['insertOid' => $insertObject,
        'referenceOid' => $referenceObject];
    if ($this->checkObjects($objectMap)) {
      // determine the sort key
      $mapper = $insertObject->getMapper();
      $type = $insertObject->getType();
      $sortkeyDef = $mapper->getSortkey();
      $sortkey = $sortkeyDef['sortFieldName'];
      $sortdir = strtoupper($sortkeyDef['sortDirection']);

      // get the sortkey values of the objects before and after the insert position
      if ($isOrderBottom) {
        $lastObject = $this->loadLastObject($type, $sortkey, $sortdir);
        $prevValue = $lastObject != null ? $this->getSortkeyValue($lastObject, $sortkey) : 1;
        $nextValue = ceil($sortdir == 'ASC' ? $prevValue+1 : $prevValue-1);
      }
      else {
        $nextValue = $this->getSortkeyValue($referenceObject, $sortkey);
        $prevObject = $this->loadPreviousObject($type, $sortkey, $nextValue, $sortdir);
        $prevValue = $prevObject != null ? $this->getSortkeyValue($prevObject, $sortkey) :
          ceil($sortdir == 'ASC' ? $nextValue-1 : $nextValue+1);
      }

      // set the sortkey value to the average
      $insertObject->setValue($sortkey, ($nextValue+$prevValue)/2);
    }
  }

  /**
   * Execute the insertBefore action
   */
  protected function doInsertBefore() {
    $request = $this->getRequest();
    $persistenceFacade = $this->getPersistenceFacade();
    $isOrderBottom = $this->isOrderBotton($request);

    // load the moved object, the reference object and the conainer object
    $insertOid = ObjectId::parse($request->getValue('insertOid'));
    $referenceOid = ObjectId::parse($request->getValue('referenceOid'));
    $containerOid = ObjectId::parse($request->getValue('containerOid'));
    $insertObject = $persistenceFacade->load($insertOid);
    $referenceObject = $isOrderBottom ? new NullNode() : $persistenceFacade->load($referenceOid);
    $containerObject = $persistenceFacade->load($containerOid);

    // check object existence
    $objectMap = ['insertOid' => $insertObject,
        'referenceOid' => $referenceObject,
        'containerOid' => $containerObject];
    if ($this->checkObjects($objectMap)) {
      $role = $request->getValue('role');
      $originalChildren = $containerObject->getValue($role);
      // add the new node to the container, if it is not yet
      $nodeExists = sizeof(Node::filter($originalChildren, $insertOid)) == 1;
      if (!$nodeExists) {
        $containerObject->addNode($insertObject, $role);
      }
      // reorder the children list
      $orderedChildren = [];
      foreach ($originalChildren as $curChild) {
        $oid = $curChild->getOID();
        if ($oid == $referenceOid) {
          $orderedChildren[] = $insertObject;
        }
        if ($oid != $insertOid) {
          $orderedChildren[] = $curChild;
        }
      }

      // get the sortkey values of the objects before and after the insert position
      if ($isOrderBottom) {
        $orderedChildren[] = $insertObject;
      }
      $containerObject->setNodeOrder($orderedChildren, [$insertObject], $role);
    }
  }

  /**
   * Load the object which order position is before the given sort value
   * @param $type The type of objects
   * @param $sortkeyName The name of the sortkey attribute
   * @param $sortkeyValue The reference sortkey value
   * @param $sortDirection The sort direction used with the sort key
   */
  protected function loadPreviousObject($type, $sortkeyName, $sortkeyValue, $sortDirection) {
    $query = new ObjectQuery($type);
    $tpl = $query->getObjectTemplate($type);
    $tpl->setValue($sortkeyName, Criteria::asValue($sortDirection == 'ASC' ? '<' : '>', $sortkeyValue), true);
    $pagingInfo = new PagingInfo(1, true);
    $objects = $query->execute(BuildDepth::SINGLE, [$sortkeyName." ".($sortDirection == 'ASC' ? 'DESC' : 'ASC')], $pagingInfo);
    return sizeof($objects) > 0 ? $objects[0] : null;
  }

  /**
   * Load the last object regarding the given sort key
   * @param $type The type of objects
   * @param $sortkeyName The name of the sortkey attribute
   * @param $sortDirection The sort direction used with the sort key
   */
  protected function loadLastObject($type, $sortkeyName, $sortDirection) {
    $query = new ObjectQuery($type);
    $pagingInfo = new PagingInfo(1, true);
    $invSortDir = $sortDirection == 'ASC' ? 'DESC' : 'ASC';
    $objects = $query->execute(BuildDepth::SINGLE, [$sortkeyName." ".$invSortDir], $pagingInfo);
    return sizeof($objects) > 0 ? $objects[0] : null;
  }

  /**
   * Check if all objects in the given array are not null and add
   * an OID_INVALID error to the response, if at least one is
   * @param $objectMap An associative array with the controller parameter names
   *        as keys and the objects to check as values
   * @return Boolean
   */
  protected function checkObjects($objectMap) {
    $response = $this->getResponse();
    $invalidOids = [];
    foreach ($objectMap as $parameterName => $object) {
      if ($object == null) {
        $invalidOids[] = $parameterName;
      }
    }
    if (sizeof($invalidOids) > 0) {
      $response->addError(ApplicationError::get('OID_INVALID',
        ['invalidOids' => $invalidOids]));
      return false;
    }
    return true;
  }

  /**
   * Check if the node should be moved to the bottom of the list
   * @param $request The request
   * @return Boolean
   */
  protected function isOrderBotton($request) {
    return ($request->getValue('referenceOid') == self::ORDER_BOTTOM);
  }

  /**
   * Get the sortkey value of an object. Defaults to the object's id, if
   * the value is null
   * @param $object The object
   * @param $valueName The name of the sortkey attribute
   * @return String
   */
  protected function getSortkeyValue($object, $valueName) {
    $value = $object->getValue($valueName);
    if ($value == null) {
      $value = $object->getOID()->getFirstId();
    }
    return $value;
  }
}
?>
