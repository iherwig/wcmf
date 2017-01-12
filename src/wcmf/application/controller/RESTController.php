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

use wcmf\lib\model\NodeUtil;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\Controller;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;

/**
 * RESTController handles requests sent from a dstore/Rest client.
 * @see http://dstorejs.io
 *
 * The controller supports the following actions:
 *
 * <div class="controller-action">
 * <div> __Action__ _default_ </div>
 * <div>
 * Handle action according to HTTP method and parameters.
 *
 * For details about the parameters, see documentation of the methods.
 *
 * | __Response Actions__   | |
 * |------------------------|-------------------------
 * | `ok`                   | In all cases
 * </div>
 * </div>
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class RESTController extends Controller {
  use \wcmf\lib\presentation\ControllerMethods;

  /**
   * @see Controller::initialize()
   */
  public function initialize(Request $request, Response $response) {
    // construct oid from className and id
    if ($request->hasValue('className') && $request->hasValue('id')) {
      $oid = new ObjectId($request->getValue('className'), $request->getValue('id'));
      $request->setValue('oid', $oid->__toString());
    }
    // construct sourceOid from className and sourceId
    if ($request->hasValue('className') && $request->hasValue('sourceId')) {
      $sourceOid = new ObjectId($request->getValue('className'), $request->getValue('sourceId'));
      $request->setValue('sourceOid', $sourceOid->__toString());
    }
    // construct oid, targetOid from sourceOid, relation and targetId
    if ($request->hasValue('sourceOid') && $request->hasValue('relation') && $request->hasValue('targetId')) {
      $sourceOid = ObjectId::parse($request->getValue('sourceOid'));
      $relatedType = $this->getRelatedType($sourceOid, $request->getValue('relation'));
      $targetOid = new ObjectId($relatedType, $request->getValue('targetId'));
      $request->setValue('targetOid', $targetOid->__toString());
      // non-collection requests
      $request->setValue('oid', $targetOid->__toString());
    }
    parent::initialize($request, $response);
  }

  /**
   * @see Controller::validate()
   */
  protected function validate() {
    $request = $this->getRequest();
    $response = $this->getResponse();
    if ($request->hasValue('className') &&
      !$this->getPersistenceFacade()->isKnownType($request->getValue('className')))
    {
      $response->addError(ApplicationError::get('PARAMETER_INVALID',
        array('invalidParameters' => array('className'))));
      return false;
    }
    if ($request->hasHeader('Position')) {
      $position = $request->getHeader('Position');
      if (!preg_match('/^before /', $position) && !preg_match('/^last$/', $position)) {
        $response->addError(ApplicationError::get('PARAMETER_INVALID',
          array('invalidParameters' => array('Position'))));
        return false;
      }
    }
    // do default validation
    return parent::validate();
  }

  /**
   * Read an object
   *
   * | Parameter        | Description
   * |------------------|-------------------------
   * | _in_ `language`  | The language of the returned object
   * | _in_ `className` | The type of returned object
   * | _in_ `id`        | The id of returned object
   * | _out_            | Single object
   */
  public function read() {
    // delegate to DisplayController
    $subResponse = $this->executeSubAction('read');
    $this->handleSubResponse($subResponse);
  }

  /**
   * Read objects of a given type
   *
   * | Parameter        | Description
   * |------------------|-------------------------
   * | _in_ `language`  | The language of the returned objects
   * | _in_ `className` | The type of returned objects
   * | _in_ `sortBy`    | _?sortBy=+foo_ for sorting the list by foo ascending or
   * | _in_ `sort`      | _?sort(+foo)_ for sorting the list by foo ascending
   * | _in_ `limit`     | _?limit(10,25)_ for loading 10 objects starting from position 25
   * | _out_            | List of objects
   */
  public function readList() {
    // delegate to ListController
    $subResponse = $this->executeSubAction('list');
    $this->handleSubResponse($subResponse);
  }

  /**
   * Read objects that are related to another object
   *
   * | Parameter        | Description
   * |------------------|-------------------------
   * | _in_ `language`  | The language of the returned objects
   * | _in_ `sourceId`  | Id of the object to which the returned objects are related (determines the object id together with _className_)
   * | _in_ `className` | The type of the object defined by _sourceId_
   * | _in_ `relation`  | Name of the relation to the object defined by _sourceId_ (determines the type of the returned objects)
   * | _in_ `sortBy`    | _?sortBy=+foo_ for sorting the list by foo ascending or
   * | _in_ `sort`      | _?sort(+foo)_ for sorting the list by foo ascending
   * | _in_ `limit`     | _?limit(10,25)_ for loading 10 objects starting from position 25
   * | _out_            | List of objects
   */
  public function readInRelation() {
    $request = $this->getRequest();

    // rewrite query if querying for a relation
    $relationName = $request->getValue('relation');

    // set the query
    $sourceOid = ObjectId::parse($request->getValue('sourceOid'));
    $persistenceFacade = $this->getPersistenceFacade();
    $sourceNode = $persistenceFacade->load($sourceOid);
    if ($sourceNode) {
      $query = NodeUtil::getRelationQueryCondition($sourceNode, $relationName);
      $request->setValue('query', $query);

      // set the class name
      $mapper = $sourceNode->getMapper();
      $relation = $mapper->getRelation($relationName);
      $otherType = $relation->getOtherType();
      $request->setValue('className', $otherType);

      // set default order
      if (!$request->hasValue('sortFieldName')) {
        $otherMapper = $persistenceFacade->getMapper($otherType);
        $sortkeyDef = $otherMapper->getSortkey($relation->getThisRole());
        if ($sortkeyDef != null) {
          $request->setValue('sortFieldName', $sortkeyDef['sortFieldName']);
          $request->setValue('sortDirection', $sortkeyDef['sortDirection']);
        }
      }
    }

    // delegate to ListController
    $subResponse = $this->executeSubAction('list');
    $this->handleSubResponse($subResponse);
  }

  /**
   * Create an object of a given type
   *
   * | Parameter        | Description
   * |------------------|-------------------------
   * | _in_ `language`  | The language of the object
   * | _in_ `className` | Type of object to create
   * | _out_            | Created object data
   *
   * The object data is contained in POST content.
   */
  public function create() {
    // delegate to SaveController
    $subResponse = $this->executeSubAction('create');

    // return object only
    $oidStr = $subResponse->hasValue('oid') ? $subResponse->getValue('oid')->__toString() : null;
    $this->handleSubResponse($subResponse, $oidStr);
  }

  /**
   * Create an object of a given type in the given relation
   *
   * | Parameter        | Description
   * |------------------|-------------------------
   * | _in_ `language`  | The language of the object
   * | _in_ `className` | Type of object to create
   * | _in_ `sourceId`  | Id of the object to which the created objects are added (determines the object id together with _className_)
   * | _in_ `relation`  | Name of the relation to the object defined by _sourceId_ (determines the type of the created/added object)
   * | _out_            | Created object data
   *
   * The object data is contained in POST content. If an existing object
   * should be added, an `oid` parameter in the object data is sufficient.
   */
  public function createInRelation() {
    $request = $this->getRequest();

    // create new object
    $sourceOid = ObjectId::parse($request->getValue('sourceOid'));
    $relatedType = $this->getRelatedType($sourceOid, $request->getValue('relation'));
    $request->setValue('className', $relatedType);
    $subResponseCreate = $this->executeSubAction('create');

    $targetOid = $subResponseCreate->getValue('oid');
    $targetOidStr = $targetOid->__toString();

    // add new object to relation
    $request->setValue('targetOid', $targetOidStr);
    $request->setValue('role', $request->getValue('relation'));
    $subResponse = $this->executeSubAction('associate');

    // add related object to subresponse similar to default update action
    $persistenceFacade = $this->getPersistenceFacade();
    $targetObj = $persistenceFacade->load($targetOid);
    $subResponse->setValue('oid', $targetOid);
    $subResponse->setValue($targetOidStr, $targetObj);
    $subResponse->setStatus($subResponseCreate->getStatus());

    // in case of success, return object only
    $oidStr = $subResponse->hasValue('oid') ? $subResponse->getValue('oid')->__toString() : '';
    $this->handleSubResponse($subResponse, $oidStr);
  }

  /**
   * Update an object or change the order
   *
   * | Parameter        | Description
   * |------------------|-------------------------
   * | _in_ `language`  | The language of the object
   * | _in_ `className` | Type of object to update
   * | _in_ `id`        | Id of object to update
   * | _out_            | Updated object data
   *
   * The object data is contained in POST content.
   */
  public function update() {
    $request = $this->getRequest();

    $oidStr = $this->getFirstRequestOid();
    $isOrderRequest = $request->hasValue('referenceOid');
    if ($isOrderRequest) {
      // change order in all objects of the same type
      $request->setValue('insertOid', $this->getFirstRequestOid());

      // delegate to SortController
      $subResponse = $this->executeSubAction('moveBefore');

      // add sorted object to subresponse similar to default update action
      if ($subResponse->getStatus() == 200) {
        $oid = ObjectId::parse($oidStr);
        $persistenceFacade = $this->getPersistenceFacade();
        $object = $persistenceFacade->load($oid);
        $subResponse->setValue('oid', $oid);
        $subResponse->setValue($oidStr, $object);
      }
    }
    else {
      // delegate to SaveController
      $subResponse = $this->executeSubAction('update');
    }

    $this->handleSubResponse($subResponse, $oidStr);
  }

  /**
   * Update an object in a relation or change the order
   *
   * | Parameter        | Description
   * |------------------|-------------------------
   * | _in_ `language`  | The language of the object
   * | _in_ `className` | Type of object to update
   * | _in_ `id`        | Id of object to update
   * | _in_ `relation`  | Relation name if an existing object should be added to a relation (determines the type of the added object)
   * | _in_ `sourceId`  | Id of the object to which the added object is related (determines the object id together with _className_)
   * | _in_ `targetId`  | Id of the object to be added to the relation (determines the object id together with _relation_)
   * | _out_            | Updated object data
   *
   * The object data is contained in POST content.
   */
  public function updateInRelation() {
    $request = $this->getRequest();

    $isOrderRequest = $request->hasValue('referenceOid');
    $request->setValue('role', $request->getValue('relation'));
    if ($isOrderRequest) {
      // change order in a relation
      $request->setValue('containerOid', $request->getValue('sourceOid'));
      $request->setValue('insertOid', $request->getValue('targetOid'));

      // delegate to SortController
      $subResponse = $this->executeSubAction('insertBefore');
    }
    else {
      // add existing object to relation
      // delegate to AssociateController
      $subResponse = $this->executeSubAction('associate');
      if ($subResponse->getStatus() == 200) {
        // and update object
        // delegate to SaveController
        $subResponse = $this->executeSubAction('update');
      }
    }

    // add related object to subresponse similar to default update action
    if ($subResponse->getStatus() == 200) {
      $targetOidStr = $request->getValue('targetOid');
      $targetOid = ObjectId::parse($targetOidStr);
      $persistenceFacade = $this->getPersistenceFacade();
      $targetObj = $persistenceFacade->load($targetOid);
      $subResponse->setValue('oid', $targetOid);
      $subResponse->setValue($targetOidStr, $targetObj);
    }

    $this->handleSubResponse($subResponse, $targetOidStr);
  }

  /**
   * Delete an object
   *
   * | Parameter        | Description
   * |------------------|-------------------------
   * | _in_ `language`  | The language of the object
   * | _in_ `className` | Type of object to delete
   * | _in_ `id`        | Id of object to delete
   */
  public function delete() {
    // delegate to DeleteController
    $subResponse = $this->executeSubAction('delete');
    $this->handleSubResponse($subResponse);
  }

  /**
   * Remove an object from a relation
   *
   * | Parameter        | Description
   * |------------------|-------------------------
   * | _in_ `language`  | The language of the object
   * | _in_ `className` | Type of object to delete
   * | _in_ `id`        | Id of object to delete
   * | _in_ `relation`  | Name of the relation to the object defined by _sourceId_ (determines the type of the deleted object)
   * | _in_ `sourceId`  | Id of the object to which the deleted object is related (determines the object id together with _className_)
   * | _in_ `targetId`  | Id of the object to be deleted from the relation (determines the object id together with _relation_)
   */
  public function deleteInRelation() {
    $request = $this->getRequest();

    // remove existing object from relation
    $request->setValue('role', $request->getValue('relation'));

    // delegate to AssociateController
    $subResponse = $this->executeSubAction('disassociate');
    $this->handleSubResponse($subResponse);
  }

  /**
   * Create the actual response from the response resulting from delegating to
   * another controller
   * @param $subResponse The response returned from the other controller
   * @param $oidStr Serialized object id of the object to return (optional)
   */
  protected function handleSubResponse(Response $subResponse, $oidStr=null) {
    $response = $this->getResponse();
    if (!$subResponse->hasErrors()) {
      $response->clearValues();
      if ($subResponse->hasValue('object')) {
        $object = $subResponse->getValue('object');
        if ($object != null) {
          $response->setValue($object->getOID()->__toString(), $object);
        }
      }
      if ($subResponse->hasValue('list')) {
        $objects = $subResponse->getValue('list');
        $response->setValues($objects);
      }
      if ($oidStr != null && $subResponse->hasValue($oidStr)) {
        $object = $subResponse->getValue($oidStr);
        $response->setValue($oidStr, $object);
        if ($subResponse->getStatus() == 201) {
          $this->setLocationHeaderFromOid($oidStr);
        }
      }
      $response->setStatus($subResponse->getStatus());
    }
    else {
      // in case of error, return default response
      $response->setValues($subResponse->getValues());
      $response->setStatus(400);
    }
  }

  /**
   * Set the location response header according to the given object id
   * @param $oidStr The serialized object id
   */
  protected function setLocationHeaderFromOid($oidStr) {
    $oid = ObjectId::parse($oidStr);
    if ($oid) {
      $response = $this->getResponse();
      $response->setHeader('Location', $oidStr);
    }
  }

  /**
   * Get the first oid from the request
   * @return String
   */
  protected function getFirstRequestOid() {
    $request = $this->getRequest();
    foreach ($request->getValues() as $key => $value) {
      if (ObjectId::isValid($key)) {
        return $key;
      }
    }
    return '';
  }

  /**
   * Get the type that is used in the given role related to the
   * given source object.
   * @param $sourceOid ObjectId of the source object
   * @param $role The role name
   * @return String
   */
  protected function getRelatedType(ObjectId $sourceOid, $role) {
    $persistenceFacade = $this->getPersistenceFacade();
    $sourceMapper = $persistenceFacade->getMapper($sourceOid->getType());
    $relation = $sourceMapper->getRelation($role);
    return $relation->getOtherType();
  }
}
?>
