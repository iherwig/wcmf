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

use wcmf\lib\config\Configuration;
use wcmf\lib\core\EventManager;
use wcmf\lib\core\Session;
use wcmf\lib\i18n\Localization;
use wcmf\lib\i18n\Message;
use wcmf\lib\model\NodeUtil;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\persistence\TransactionEvent;
use wcmf\lib\presentation\ActionMapper;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\Controller;
use wcmf\lib\presentation\ControllerMethods;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;
use wcmf\lib\security\PermissionManager;

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
  use ControllerMethods;

  private $eventManager = null;

  /**
   * Constructor
   * @param $session
   * @param $persistenceFacade
   * @param $permissionManager
   * @param $actionMapper
   * @param $localization
   * @param $message
   * @param $configuration
   * @param $eventManager
   */
  public function __construct(Session $session,
          PersistenceFacade $persistenceFacade,
          PermissionManager $permissionManager,
          ActionMapper $actionMapper,
          Localization $localization,
          Message $message,
          Configuration $configuration,
          EventManager $eventManager) {
    parent::__construct($session, $persistenceFacade, $permissionManager,
            $actionMapper, $localization, $message, $configuration);
    $this->eventManager = $eventManager;
    // add transaction listener
    $this->eventManager->addListener(TransactionEvent::NAME, [$this, 'afterCommit']);
  }

  /**
   * Destructor
   */
  public function __destruct() {
    $this->eventManager->removeListener(TransactionEvent::NAME, [$this, 'afterCommit']);
  }

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
        ['invalidParameters' => ['className']]));
      return false;
    }
    if ($request->hasHeader('Position')) {
      $position = $request->getHeader('Position');
      if (!preg_match('/^before /', $position) && !preg_match('/^last$/', $position)) {
        $response->addError(ApplicationError::get('PARAMETER_INVALID',
          ['invalidParameters' => ['Position']]));
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
   * | _in_ `sortBy`    | <em>?sortBy=+foo</em> for sorting the list by foo ascending or
   * | _in_ `sort`      | <em>?sort(+foo)</em> for sorting the list by foo ascending
   * | _in_ `limit`     | <em>?limit(10,25)</em> for loading 10 objects starting from position 25
   * | _in_ `query`     | A query condition encoded in RQL to be used with StringQuery::setRQLConditionString()
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
   * | _in_ `sortBy`    | <em>?sortBy=+foo</em> for sorting the list by foo ascending or
   * | _in_ `sort`      | <em>?sort(+foo)</em> for sorting the list by foo ascending
   * | _in_ `limit`     | <em>?limit(10,25)</em> for loading 10 objects starting from position 25
   * | _in_ `query`     | A query condition encoded in RQL to be used with StringQuery::setRQLConditionString()
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
      $relationQuery = NodeUtil::getRelationQueryCondition($sourceNode, $relationName);
      $query = ($request->hasValue('query') ? $request->getValue('query').'&' : '').$relationQuery;
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
    $this->requireTransaction();
    // delegate to SaveController
    $subResponse = $this->executeSubAction('create');

    // return object only
    $oidStr = $subResponse->hasValue('oid') ? $subResponse->getValue('oid')->__toString() : null;
    $this->handleSubResponse($subResponse, $oidStr);

    // prevent commit
    if ($subResponse->hasErrors()) {
      $this->endTransaction(false);
    }
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
    $this->requireTransaction();
    $request = $this->getRequest();

    // create new object
    $oidStr = null;
    $sourceOid = ObjectId::parse($request->getValue('sourceOid'));
    $relatedType = $this->getRelatedType($sourceOid, $request->getValue('relation'));
    $request->setValue('className', $relatedType);
    $subResponse = $this->executeSubAction('create');
    if (!$subResponse->hasErrors()) {
      $createStatus = $subResponse->getStatus();
      $targetOid = $subResponse->getValue('oid');
      $targetOidStr = $targetOid->__toString();

      // add new object to relation
      $request->setValue('targetOid', $targetOidStr);
      $request->setValue('role', $request->getValue('relation'));
      $subResponse = $this->executeSubAction('associate');
      if (!$subResponse->hasErrors()) {
        // add related object to subresponse similar to default update action
        $persistenceFacade = $this->getPersistenceFacade();
        $targetObj = $persistenceFacade->load($targetOid);
        $subResponse->setValue('oid', $targetOid);
        $subResponse->setValue($targetOidStr, $targetObj);
        $subResponse->setStatus($createStatus);

        // in case of success, return object only
        $oidStr = $subResponse->hasValue('oid') ? $subResponse->getValue('oid')->__toString() : '';
      }
    }

    // prevent commit
    if ($subResponse->hasErrors()) {
      $this->endTransaction(false);
    }
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
    $this->requireTransaction();
    $request = $this->getRequest();

    $oidStr = $this->getFirstRequestOid();
    $isOrderRequest = $request->hasValue('referenceOid');
    if ($isOrderRequest) {
      // change order in all objects of the same type
      $request->setValue('insertOid', $this->getFirstRequestOid());

      // delegate to SortController
      $subResponse = $this->executeSubAction('moveBefore');

      // add sorted object to subresponse similar to default update action
      if (!$subResponse->hasErrors()) {
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

    // prevent commit
    if ($subResponse->hasErrors()) {
      $this->endTransaction(false);
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
    $this->requireTransaction();
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
      // update existing object
      // delegate to SaveController
      // NOTE: we need to update first, otherwise the update action might override
      // the foreign keys changes from the associate action
      $subResponse = $this->executeSubAction('update');
      if (!$subResponse->hasErrors()) {
        // and add object to relation
        // delegate to AssociateController
        $subResponse = $this->executeSubAction('associate');
      }
    }

    // add related object to subresponse similar to default update action
    if (!$subResponse->hasErrors()) {
      $targetOidStr = $request->getValue('targetOid');
      $targetOid = ObjectId::parse($targetOidStr);
      $persistenceFacade = $this->getPersistenceFacade();
      $targetObj = $persistenceFacade->load($targetOid);
      $subResponse->setValue('oid', $targetOid);
      $subResponse->setValue($targetOidStr, $targetObj);
    }

    // prevent commit
    if ($subResponse->hasErrors()) {
      $this->endTransaction(false);
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
    $this->requireTransaction();
    // delegate to DeleteController
    $subResponse = $this->executeSubAction('delete');

    // prevent commit
    if ($subResponse->hasErrors()) {
      $this->endTransaction(false);
    }
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
    $this->requireTransaction();
    $request = $this->getRequest();

    // remove existing object from relation
    $request->setValue('role', $request->getValue('relation'));

    // delegate to AssociateController
    $subResponse = $this->executeSubAction('disassociate');

    // prevent commit
    if ($subResponse->hasErrors()) {
      $this->endTransaction(false);
    }
    $this->handleSubResponse($subResponse);
  }

  /**
   * Create the actual response from the response resulting from delegating to
   * another controller
   * @param $subResponse The response returned from the other controller
   * @param $oidStr Serialized object id of the object to return (optional)
   * @return Boolean whether an error occured or not
   */
  protected function handleSubResponse(Response $subResponse, $oidStr=null) {
    $response = $this->getResponse();
    if (!$subResponse->hasErrors()) {
      $response->clearValues();
      $response->setHeaders($subResponse->getHeaders());
      $response->setStatus($subResponse->getStatus());
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
      return true;
    }

    // in case of error, return default response
    $response->setErrors($subResponse->getErrors());
    $response->setStatus(400);
    return false;
  }

  /**
   * Update oids after commit
   * @param $event
   */
  public function afterCommit(TransactionEvent $event) {
    if ($event->getPhase() == TransactionEvent::AFTER_COMMIT) {
      $response = $this->getResponse();
      $locationOid = $response->getHeader('Location');

      // replace changed oids
      $changedOids = $event->getInsertedOids();
      foreach ($changedOids as $oldOid => $newOid) {
        if ($response->hasValue($oldOid)) {
          $value = $response->getValue($oldOid);
          $response->setValue($newOid, $value);
          $response->clearValue($oldOid);
        }
        if ($locationOid == $oldOid) {
          $this->setLocationHeaderFromOid($newOid);
        }
      }
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
