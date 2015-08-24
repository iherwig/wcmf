<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2015 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\application\controller;

use wcmf\lib\model\NullNode;
use wcmf\lib\model\ObjectQuery;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\persistence\ObjectId;
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
        array('invalidOids' => array($request->getValue('insertOid')))));
      return false;
    }
    $referenceOid = ObjectId::parse($request->getValue('referenceOid'));
    if (!$referenceOid && !$isOrderBottom) {
      $response->addError(ApplicationError::get('OID_INVALID',
        array('invalidOids' => array($request->getValue('referenceOid')))));
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
          array('invalidOids' => array($request->getValue('containerOid')))));
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
  protected function doExecute() {
    $request = $this->getRequest();
    $response = $this->getResponse();
    $transaction = $this->getPersistenceFacade()->getTransaction();

    // do actions
    $transaction->begin();
    if ($request->getAction() == 'moveBefore') {
      $this->doMoveBefore();
    }
    else if ($request->getAction() == 'insertBefore') {
      $this->doInsertBefore();
    }
    $transaction->commit();

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
    $objectMap = array('insertOid' => $insertObject,
        'referenceOid' => $referenceObject);
    if ($this->checkObjects($objectMap)) {
      // determine the sort key
      $mapper = $insertObject->getMapper();
      $sortkeyDef = $mapper->getSortkey();
      $sortkey = $sortkeyDef['sortFieldName'];

      // determine the sort boundaries
      $referenceValue = $isOrderBottom ? self::UNBOUND : $this->getSortkeyValue($referenceObject, $sortkey);
      $insertValue = $this->getSortkeyValue($insertObject, $sortkey);

      // determine the sort direction
      $isSortup = false;
      if ($referenceValue == self::UNBOUND || $referenceValue > $insertValue) {
        $isSortup = true;
      }

      // load the objects in the sortkey range
      $objects = array();
      $type = $insertObject->getType();
      if ($isSortup) {
        $objects = $this->loadObjectsInSortkeyRange($type, $sortkey, $insertValue, $referenceValue);
      }
      else {
        $objects = $this->loadObjectsInSortkeyRange($type, $sortkey, $referenceValue, $insertValue);
      }

      // add insert (and reference) object at the correct end of the list
      if ($isSortup) {
        $objects[] = $insertObject;
        // sortkey of reference object does not change
        // update sort keys
        $count=sizeof($objects);
        $lastValue = $this->getSortkeyValue($objects[$count-1], $sortkey);
        for ($i=$count-1; $i>0; $i--) {
          $objects[$i]->setValue($sortkey, $this->getSortkeyValue($objects[$i-1], $sortkey));
        }
        $objects[0]->setValue($sortkey, $lastValue);
      }
      else {
        array_unshift($objects, $referenceObject);
        array_unshift($objects, $insertObject);
        // update sort keys
        $count=sizeof($objects);
        $firstValue = $this->getSortkeyValue($objects[0], $sortkey);
        for ($i=0; $i<$count-1; $i++) {
          $objects[$i]->setValue($sortkey, $this->getSortkeyValue($objects[$i+1], $sortkey));
        }
        $objects[$count-1]->setValue($sortkey, $firstValue);
      }
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
    $containerObject = $persistenceFacade->load($containerOid, 1);
    // check object existence
    $objectMap = array('insertOid' => $insertObject,
        'referenceOid' => $referenceObject,
        'containerOid' => $containerObject);
    if ($this->checkObjects($objectMap)) {
      // add the new node to the container, if it is not yet
      $nodeExists = sizeof($containerObject->getChildrenEx($insertOid)) == 1;
      if (!$nodeExists) {
        $containerObject->addNode($insertObject, $request->getValue('role'));
      }
      // reorder the children list
      $children = $containerObject->getChildren();
      $newChildren = array();
      foreach ($children as $curChild) {
        $oid = $curChild->getOID();
        if ($oid == $referenceOid) {
          $newChildren[] = $insertObject;
        }
        if ($oid != $insertOid) {
          $newChildren[] = $curChild;
        }
      }
      if ($isOrderBottom) {
        $newChildren[] = $insertObject;
      }
      $containerObject->setNodeOrder($newChildren);
    }
  }

  /**
   * Load all objects between two sortkey values
   * @param $type The type of objects
   * @param $sortkeyName The name of the sortkey attribute
   * @param $lowerValue The lower value of the sortkey or UNBOUND
   * @param $upperValue The upper value of the sortkey or UNBOUND
   */
  protected function loadObjectsInSortkeyRange($type, $sortkeyName, $lowerValue, $upperValue) {
    $query = new ObjectQuery($type);
    $tpl1 = $query->getObjectTemplate($type);
    $tpl2 = $query->getObjectTemplate($type);
    if ($lowerValue != self::UNBOUND) {
      $tpl1->setValue($sortkeyName, Criteria::asValue('>', $lowerValue), true);
    }
    if ($upperValue != self::UNBOUND) {
      $tpl2->setValue($sortkeyName, Criteria::asValue('<', $upperValue), true);
    }
    $objects = $query->execute(BuildDepth::SINGLE);
    return $objects;
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
    $invalidOids = array();
    foreach ($objectMap as $parameterName => $object) {
      if ($object == null) {
        $invalidOids[] = $parameterName;
      }
    }
    if (sizeof($invalidOids) > 0) {
      $response->addError(ApplicationError::get('OID_INVALID',
        array('invalidOids' => $invalidOids)));
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
