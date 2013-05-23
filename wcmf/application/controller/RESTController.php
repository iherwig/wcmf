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
 * $Id: class.RESTController.php 1250 2010-12-05 23:02:43Z iherwig $
 */
namespace wcmf\application\controller;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\NodeUtil;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\Controller;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;

/**
 * RESTController is a controller that handles REST requests.
 *
 * <b>Input actions:</b>
 * - unspecified: Handle action according to HTTP method and parameters
 *
 * <b>Output actions:</b>
 * - depends on the controller, to that the action is delegated
 *
 * For details about the paramters, see documentation for the methods
 * RESTController::handleGet(), RESTController::handlePost(),
 * RESTController::handlePut(), RESTController::handleDelete()
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class RESTController extends Controller {

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
    // construct targetOid from sourceOid, relation and targetId
    if ($request->hasValue('sourceOid') && $request->hasValue('relation') && $request->hasValue('targetId')) {
      $sourceOid = ObjectId::parse($request->getValue('sourceOid'));
      $relatedType = $this->getRelatedType($sourceOid, $request->getValue('relation'));
      $targetOid = new ObjectId($relatedType, $request->getValue('targetId'));
      $request->setValue('targetOid', $targetOid->__toString());
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
      !ObjectFactory::getInstance('persistenceFacade')->isKnownType($request->getValue('className')))
    {
      $response->addError(ApplicationError::get('PARAMETER_INVALID',
        array('invalidParameters' => array('className'))));
      return false;
    }
    // do default validation
    return parent::validate();
  }

  /**
   * Execute the requested REST action
   * @see Controller::executeKernel()
   */
  public function executeKernel() {
    $request = $this->getRequest();
    $result = false;
    switch ($request->getMethod()) {
      case 'GET':
        $result = $this->handleGet();
        break;
      case 'POST':
        $result = $this->handlePost();
        break;
      case 'PUT':
        $result = $this->handlePut();
        break;
      case 'DELETE':
        $result = $this->handleDelete();
        break;
      default:
        $result = $this->handleGet();
        break;
    }
    return $result;
  }

  /**
   * Handle a GET request (read object(s) of a given type)
   *
   * Request parameters:
   * - collection: Boolean wether to load one object or a list of objects
   * - language: The language of the returned object(s)
   * - className: The type of returned object(s)
   * - id: If collection is false, the object with className/id will be loaded
   *
   * - sortBy: ?sortBy=+foo for sorting the list by foo ascending or
   * - sort: ?sort(+foo) for sorting the list by foo ascending
   *
   * - relation: relation name if objects in relation to another object should
   *             be loaded (determines the type of the returned objects)
   * - sourceId: id of the object to which the returned objects are related
   *             (determines the object id together with className)
   *
   * Range header is used to get only part of the list
   *
   * Response parameters:
   * - Single object or list of objects. In case of a list, the Content-Range
   *   header will be set.
   */
  protected function handleGet() {
    $request = $this->getRequest();
    $response = $this->getResponse();

    if ($request->getBooleanValue('collection') === false) {
      // read a specific object
      // delegate further processing to DisplayController

      // execute action
      $subResponse = $this->executeSubAction('read');

      // return object list only
      $object = $subResponse->getValue('object');
      $response->clearValues();
      if ($object != null) {
        $response->setValue($object->getOID()->__toString(), $object);
      }
      else {
        $response->setStatus('404 Not Found');
      }

      // set the response headers
      //$this->setLocationHeaderFromOid($request->getValue('oid'));
    }
    else {
      // read all objects of the given type
      // delegate further processing to ListController
      $offset = 0;

      // transform range header
      if ($request->hasHeader('Range')) {
        if (preg_match('/^items=([\-]?[0-9]+)-([\-]?[0-9]+)$/', $request->getHeader('Range'), $matches)) {
          $offset = intval($matches[1]);
          $limit = intval($matches[2])-$offset+1;
          $request->setValue('offset', $offset);
          $request->setValue('limit', $limit);
        }
      }

      // transform sort definition
      foreach ($request->getValues() as $key => $value) {
        if (preg_match('/^sort\(([^\)]+)\)$|sortBy=([.]+)$/', $key, $matches)) {
          $sortDefs = preg_split('/,/', $matches[1]);
          // ListController allows only one sortfield
          $sortDef = $sortDefs[0];
          $sortFieldName = substr($sortDef, 1);
          $sortDirection = preg_match('/^-/', $sortDef) ? 'desc' : 'asc';
          $request->setValue('sortFieldName', $sortFieldName);
          $request->setValue('sortDirection', $sortDirection);
          break;
        }
      }

      // TODO: create query from optional GET values

      // rewrite query if querying for a relation
      if ($request->hasValue("relation") && $request->hasValue('sourceOid')) {
        $relationName = $request->getValue("relation");

        // set the query
        $sourceOid = ObjectId::parse($request->getValue('sourceOid'));
        $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
        $sourceNode = $persistenceFacade->load($sourceOid);
        if ($sourceNode) {
          $query = NodeUtil::getRelationQueryCondition($sourceNode, $relationName);
          $request->setValue('query', $query);

          // set the class name
          $mapper = $sourceNode->getMapper();
          $relation = $mapper->getRelation($relationName);
          $request->setValue('className', $relation->getOtherType());
        }
      }

      // execute action
      $subResponse = $this->executeSubAction('list');

      // return object list only
      $objects = $subResponse->getValue('list');
      $response->clearValues();
      $response->setValues($objects);

      // set response range header
      $limit = sizeof($objects);
      $total = $subResponse->getValue('totalCount');
      $response->setHeader('Content-Range', 'items '.$offset.'-'.($offset+$limit-1).'/'.$total);
    }

    return false;
  }

  /**
   * @see Controller::assignResponseDefaults()
   */
  protected function assignResponseDefaults() {
    if (sizeof($this->getResponse()->getErrors()) > 0) {
      parent::assignResponseDefaults();
    }
    // don't add anything in case of success
  }

  /**
   * Handle a POST request (create an object of a given type)
   *
   * Request parameters:
   * - language: The language of the object
   * - className: Type of object to create
   *
   * - relation: relation name if the object should be created/added in
   *             relation to another object (determines the type of the created/added object)
   * - sourceId: id of the object to which the created/added object is related
   *             (determines the object id together with className)
   *
   * The object data is contained in POST content. If an existing object
   * should be added, an 'oid' parameter is sufficient
   *
   * Response parameters:
   * - Created object data
   */
  protected function handlePost() {
    $request = $this->getRequest();
    if ($request->hasValue('relation') && $request->hasValue('sourceOid')) {
      $sourceOid = ObjectId::parse($request->getValue('sourceOid'));
      $relatedType = $this->getRelatedType($sourceOid, $request->getValue('relation'));
      $request->setValue('className', $relatedType);
      $subResponseCreate = $this->executeSubAction('create');

      // add new object to relation
      $request->setValue('targetOid', $subResponseCreate->getValue('oid')->__toString());
      $request->setValue('role', $request->getValue('relation'));
      $subResponse = $this->executeSubAction('associate');

      // add related object to subresponse similar to default update action
      $targetOidStr = $request->getValue('targetOid');
      $targetOid = ObjectId::parse($targetOidStr);
      $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
      $targetObj = $persistenceFacade->load($targetOid);
      $subResponse->setValue('oid', $targetOid);
      $subResponse->setValue($targetOidStr, $targetObj);
    }
    else {
      $subResponse = $this->executeSubAction('create');
    }
    $response = $this->getResponse();

    // in case of success, return object only
    $oidStr = $subResponse->hasValue('oid') ? $subResponse->getValue('oid')->__toString() : '';
    if (!$subResponse->hasErrors()) {
      $response->clearValues();
      if ($subResponse->hasValue($oidStr)) {
        $object = $subResponse->getValue($oidStr);
        $response->setValue($oidStr, $object);
      }
    }
    else {
      // in case of error, return default response
      $response->setValues($subResponse->getValues());
    }

    // set the response headers
    $response->setStatus('201 Created');
    $this->setLocationHeaderFromOid($response->getValue('oid'));

    return false;
  }

  /**
   * Handle a PUT request (update an object of a given type)
   *
   * Request parameters:
   * - language: The language of the object
   * - className: Type of object to update
   * - id: Id of object to update
   *
   * - relation: relation name if an existing object should be added to a
   *             relation (determines the type of the added object)
   * - sourceId: id of the object to which the added object is related
   *             (determines the object id together with className)
   * - targetId: id of the object to be added to the relation
   *             (determines the object id together with relation)
   *
   * The object data is contained in POST content.
   *
   * Response parameters:
   * - Updated object data
   */
  protected function handlePut() {
    $request = $this->getRequest();
    if ($request->hasValue('relation') && $request->hasValue('sourceOid') &&
            $request->hasValue('targetOid')) {
      // add existing object to relation
      $request->setValue('role', $request->getValue('relation'));
      $subResponse = $this->executeSubAction('associate');

      // add related object to subresponse similar to default update action
      $targetOidStr = $request->getValue('targetOid');
      $targetOid = ObjectId::parse($targetOidStr);
      $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
      $targetObj = $persistenceFacade->load($targetOid);
      $subResponse->setValue('oid', $targetOid);
      $subResponse->setValue($targetOidStr, $targetObj);
    }
    else {
      // update object
      $subResponse = $this->executeSubAction('update');
    }

    $response = $this->getResponse();

    // in case of success, return object only
    $oidStr = $this->getFirstRequestOid();
    if (!$subResponse->hasErrors() && $subResponse->hasValue($oidStr)) {
      $object = $subResponse->getValue($oidStr);
      $response->clearValues();
      $response->setValue($oidStr, $object);
    }
    else {
      // in case of error, return default response
      $response->setValues($subResponse->getValues());
    }

    // set the response headers
    $response->setStatus('202 Accepted');
    $this->setLocationHeaderFromOid($response->getValue('oid'));

    return false;
  }

  /**
   * Handle a DELETE request (delete an object of a given type)
   *
   * Request parameters:
   * - language: The language of the object
   * - className: Type of object to delete
   * - id: Id of object to delete
   *
   * - relation: relation name if the object should be deleted from a
   *             relation to another object (determines the type of the deleted object)
   * - sourceId: id of the object to which the deleted object is related
   *             (determines the object id together with className)
   * - targetId: id of the object to be deleted from the relation
   *             (determines the object id together with relation)
   *
   * Response parameters:
   * empty
   */
  protected function handleDelete() {
    $request = $this->getRequest();
    if ($request->hasValue('relation') && $request->hasValue('sourceOid') &&
            $request->hasValue('targetOid')) {
      // remove existing object from relation
      $request->setValue('role', $request->getValue('relation'));
      $subResponse = $this->executeSubAction('disassociate');
    }
    else {
      // delete object
      $subResponse = $this->executeSubAction('delete');
    }

    $response = $this->getResponse();
    $response->setValues($subResponse->getValues());

    // set the response headers
    $response->setStatus('204 No Content');

    return false;
  }

  /**
   * Set the location response header according to the given object id
   * @param oid The serialized object id
   */
  protected function setLocationHeaderFromOid($oid) {
    $oid = ObjectId::parse($oid);
    if ($oid) {
      $response = $this->getResponse();
      $response->setHeader('Location', $oid->__toString());
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
   * @param sourceOid ObjectId of the source object
   * @param role The role name
   * @return String
   */
  protected function getRelatedType(ObjectId $sourceOid, $role) {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $sourceMapper = $persistenceFacade->getMapper($sourceOid->getType());
    $relation = $sourceMapper->getRelation($role);
    return $relation->getOtherType();
  }
}
?>
