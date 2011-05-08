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
require_once(WCMF_BASE."wcmf/lib/presentation/class.Controller.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/model/class.Node.php");
require_once(WCMF_BASE."wcmf/lib/model/class.NodeIterator.php");
require_once(WCMF_BASE."wcmf/lib/model/class.ObjectQuery.php");
require_once(WCMF_BASE."wcmf/lib/visitor/class.CommitVisitor.php");

/**
 * @class SortController
 * @ingroup Controller
 * @brief SortController is a controller that changes the order of nodes.
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
 * @param[in] role The role, that the inserted object should have in the container object.
 *
 * @author   ingo herwig <ingo@wemove.com>
 */
class SortController extends Controller
{
  private static $ORDER_BOTTOM = 'ORDER_BOTTOM';

  /**
   * @see Controller::hasView()
   */
  public function hasView()
  {
    return false;
  }
  /**
   * @see Controller::validate()
   */
  protected function validate()
  {
    $request = $this->getRequest();
    $response = $this->getResponse();

    $isOrderBottom = ($request->getValue('referenceOid') == self::$ORDER_BOTTOM);

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

    if ($request->getAction() == 'insertBefore') {
      $containerOid = ObjectId::parse($request->getValue('containerOid'));
      if(!$containerOid) {
        $response->addError(ApplicationError::get('OID_INVALID',
          array('invalidOids' => array($request->getValue('containerOid')))));
        return false;
      }
    }

    // check if object supports order
    $mapper = PersistenceFacade::getInstance()->getMapper($insertOid->getType());
    if (!$mapper->isSortable()) {
      $response->addError(ApplicationError::get('ORDER_NOT_SUPPORTED'));
      return false;
    }

    // check matching classes for move operation
    if ($request->getAction() == 'moveBefore') {
      if ($insertOid->getType() != $referenceOid->getType()) {
        $response->addError(ApplicationError::get('CLASSES_DO_NOT_MATCH'));
        return false;
      }
    }

    // check association for insert operation
    if ($request->getAction() == 'insertBefore')
    {
      $mapper = PersistenceFacade::getInstance()->getMapper($containerOid->getType());
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
    }
    return true;
  }
  /**
   * Sort Nodes.
   * @return True in every case.
   * @see Controller::executeKernel()
   */
  protected function executeKernel()
  {
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
    return true;
  }

  /**
   * Execute the moveBefore action
   */
  protected function doMoveBefore()
  {
    $request = $this->getRequest();
    $persistenceFacade = PersistenceFacade::getInstance();

    // load the moved object and the reference object
    $insertOid = ObjectId::parse($request->getValue('insertOid'));
    $referenceOid = ObjectId::parse($request->getValue('referenceOid'));
    $insertObject = $persistenceFacade->load($insertOid);
    $referenceObject = $persistenceFacade->load($referenceOid);
    if ($insertObject != null && $referenceObject != null) {
      // determine the sort key
      $mapper = $insertObject->getMapper();
      $sortDef = $mapper->getDefaultOrder();
      $sortKey = $sortDef['sortFieldName'];

      // determine boundaries and sort direction
      $referenceValue = $referenceObject->getValue($sortKey);
      $insertValue = $insertObject->getValue($sortKey);
      $isSortUp = false;
      if ($referenceValue > $insertValue) {
        $isSortUp = true;
      }

      // get all objects between the boundaries
      $objects = array();
      $type = $mapper->getType();
      if ($isSortUp) {
        $objects = $this->loadObjectsInSortRange($type, $sortKey,
                $insertValue, $referenceValue);
      }
      else {
        $objects = $this->loadObjectsInSortRange($type, $sortKey,
                $referenceValue, $insertValue);
      }

      // add insert (and reference) object at the correct
      // end of the list
      if ($isSortUp) {
        array_push($objects, $insertObject);
        // sortkey of reference object does not change
        // update sort keys
        $count=sizeof($objects);
        $lastValue = $objects[$count-1]->getValue($sortKey);
        for ($i=$count-1; $i>0; $i--) {
          $objects[$i]->setValue($sortKey, $objects[$i-1]->getValue($sortKey));
        }
        $objects[0]->setValue($sortKey, $lastValue);
      }
      else {
        array_unshift($objects, $referenceObject);
        array_unshift($objects, $insertObject);
        // update sort keys
        $count=sizeof($objects);
        $firstValue = $objects[0]->getValue($sortKey);
        for ($i=0; $i<$count-1; $i++) {
          $objects[$i]->setValue($sortKey, $objects[$i+1]->getValue($sortKey));
        }
        $objects[$count-1]->setValue($sortKey, $firstValue);
      }

      // commit changes
      for ($i=0, $count=sizeof($objects); $i<$count; $i++) {
        $objects[$i]->save();
      }
    }
    else
    {
      // at least one of the objects does not exist
      if ($insertObject == null) {
        $this->addError(ApplicationError::get('OID_INVALID',
          array('invalidOids' => array('insertOid'))));
      }
      if ($referenceObject == null) {
        $this->addError(ApplicationError::get('OID_INVALID',
          array('invalidOids' => array('referenceOid'))));
      }
    }
  }

  /**
   * Execute the moveBefore action
   */
  protected function doInsertBefore()
  {
  }

  /**
   * Load all objects between two sortkey values
   * @param type The type of objects
   * @param sortKeyName The name of the sortkey attribute
   * @param lowerValue The lower value of the sortkey
   * @param upperValue The upper value of the sortkey
   */
  protected function loadObjectsInSortRange($type, $sortKeyName, $lowerValue, $upperValue)
  {
    $query = new ObjectQuery($type);
    $tpl1 = $query->getObjectTemplate($type);
    $tpl2 = $query->getObjectTemplate($type);
    $tpl1->setValue($sortKeyName, Criteria::asValue('>', $lowerValue));
    $tpl2->setValue($sortKeyName, Criteria::asValue('<', $upperValue));
    $objects = $query->execute(BUILDDEPTH_SINGLE);
    return $objects;
  }
}
?>
