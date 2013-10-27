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

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\NullNode;
use wcmf\lib\model\ObjectQuery;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\Controller;

/**
 * SortController is a controller that changes the order of nodes.
 *
 * Nodes can either be sorted in a list of nodes of the same type (moveBefore
 * action) or in a list of child nodes of a container node (insertBefore action).
 *
 * <b>Input actions:</b>
 * - @em moveBefore Insert an object before a reference object in the list of
 * all objects of the same type
 * - @em insertBefore Insert an object before a reference object in the
 * order of a container object
 *
 * <b>Output actions:</b>
 * - @em ok In any case
 *
 * @param[in] containerOid The oid of the container object (insertBefore action only).
 * @param[in] insertOid The oid of the object to insert/move.
 * @param[in] referenceOid The oid of the object to insert the inserted object before.
 *            If the inserted object should be the last in the container order,
 *            the referenceOid contains the special value ORDER_BOTTOM
 * @param[in] role The role, that the inserted object should have in the container object
 *            (insertBefore action only).
 *
 * @author   ingo herwig <ingo@wemove.com>
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

    $isOrderBottom = $this->isOrderBotton($request);

    // check object id validity
    $insertOid = ObjectId::parse($request->getValue('insertOid'));
    if(!$insertOid) {
      $response->addError(ApplicationError::get('OID_INVALID',
        array('invalidOids' => array($request->getValue('insertOid')))));
      return false;
    }
    $referenceOid = ObjectId::parse($request->getValue('referenceOid'));
    if(!$referenceOid && !$isOrderBottom) {
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
      $mapper = ObjectFactory::getInstance('persistenceFacade')->getMapper($insertOid->getType());
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
      $mapper = ObjectFactory::getInstance('persistenceFacade')->getMapper($containerOid->getType());
      $relationDesc = null;
      // try role
      if ($request->hasValue('role')) {
        $relationDesc = $mapper->getRelation($request->getValue('role'));
        if ($relationDesc == null) {
          $response->addError(ApplicationError::get('ROLE_INVALID'));
          return false;
        }
      }
      // try type
      else {
        $relationDesc = $mapper->getRelation($insertOid->getType());
        if ($relationDesc == null) {
          $response->addError(ApplicationError::get('ASSOCIATION_INVALID'));
          return false;
        }
      }
      // check if object supports order
      if ($relationDesc) {
        $otherMapper = $relationDesc->getOtherMapper();
        if (!$otherMapper->isSortable($relationDesc->getThisRole())) {
          $response->addError(ApplicationError::get('ORDER_NOT_SUPPORTED'));
          return false;
        }
      }
    }
    return true;
  }

  /**
   * Sort Nodes.
   * @return True in every case.
   * @see Controller::executeKernel()
   */
  protected function executeKernel() {
    $request = $this->getRequest();
    $response = $this->getResponse();
    $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();

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
    return true;
  }

  /**
   * Execute the moveBefore action
   */
  protected function doMoveBefore() {
    $request = $this->getRequest();
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
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
      $sortDef = $mapper->getDefaultOrder();
      $sortkey = $sortDef['sortFieldName'];

      // determine the sort boundaries
      $referenceValue = $isOrderBottom ? self::UNBOUND : $referenceObject->getValue($sortkey);
      $insertValue = $insertObject->getValue($sortkey);

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
        array_push($objects, $insertObject);
        // sortkey of reference object does not change
        // update sort keys
        $count=sizeof($objects);
        $lastValue = $objects[$count-1]->getValue($sortkey);
        for ($i=$count-1; $i>0; $i--) {
          $objects[$i]->setValue($sortkey, $objects[$i-1]->getValue($sortkey));
        }
        $objects[0]->setValue($sortkey, $lastValue);
      }
      else {
        array_unshift($objects, $referenceObject);
        array_unshift($objects, $insertObject);
        // update sort keys
        $count=sizeof($objects);
        $firstValue = $objects[0]->getValue($sortkey);
        for ($i=0; $i<$count-1; $i++) {
          $objects[$i]->setValue($sortkey, $objects[$i+1]->getValue($sortkey));
        }
        $objects[$count-1]->setValue($sortkey, $firstValue);
      }
    }
  }

  /**
   * Execute the moveBefore action
   */
  protected function doInsertBefore() {
    $request = $this->getRequest();
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $isOrderBottom = $this->isOrderBotton($request);

    // load the moved object, the reference object and the conainer object
    $insertOid = ObjectId::parse($request->getValue('insertOid'));
    $referenceOid = ObjectId::parse($request->getValue('referenceOid'));
    $containerOid = ObjectId::parse($request->getValue('containerOid'));
    $insertObject = $persistenceFacade->load($insertOid);
    $containerObject = $persistenceFacade->load($containerOid, 1);

    $referenceObject = null;
    if ($isOrderBottom) {
      $referenceObject = new NullNode();
    }
    else {
      $referenceObjects = $containerObject->getChildrenEx($referenceOid);
      if (sizeof($referenceObjects) == 1) {
        $referenceObject = $referenceObjects[0];
      }
    }

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
   * @param type The type of objects
   * @param sortkeyName The name of the sortkey attribute
   * @param lowerValue The lower value of the sortkey or UNBOUND
   * @param upperValue The upper value of the sortkey or UNBOUND
   */
  protected function loadObjectsInSortkeyRange($type, $sortkeyName, $lowerValue, $upperValue) {
    $query = new ObjectQuery($type);
    $tpl1 = $query->getObjectTemplate($type);
    $tpl2 = $query->getObjectTemplate($type);
    if ($lowerValue != self::UNBOUND) {
      $tpl1->setValue($sortkeyName, Criteria::asValue('>', $lowerValue));
    }
    if ($upperValue != self::UNBOUND) {
      $tpl2->setValue($sortkeyName, Criteria::asValue('<', $upperValue));
    }
    $objects = $query->execute(BuildDepth::SINGLE);
    return $objects;
  }

  /**
   * Check if all objects in the given array are not null and add
   * an OID_INVALID error to the response, if at least one is
   * @param objectMap An associative array with the controller parameter names
   *        as keys and the objects to check as values
   * @return Boolean
   */
  protected function checkObjects($objectMap) {
    $invalidOids = array();
    foreach ($objectMap as $parameterName => $object) {
      if ($object == null) {
        $invalidOids[] = $parameterName;
      }
    }
    if (sizeof($invalidOids) > 0) {
      $this->addError(ApplicationError::get('OID_INVALID',
        array('invalidOids' => $invalidOids)));
      return false;
    }
    return true;
  }

  /**
   * Check if the node should be moved to the bottom of the list
   * @param request The request
   * @return Boolean
   */
  protected function isOrderBotton($request) {
    return ($request->getValue('referenceOid') == self::ORDER_BOTTOM);
  }
}
?>
