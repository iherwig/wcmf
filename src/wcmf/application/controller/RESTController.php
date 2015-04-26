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

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\NodeUtil;
use wcmf\lib\model\ObjectQuery;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\Controller;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;

/**
 * RESTController handles REST requests.
 *
 * The controller supports the following actions:
 *
 * <div class="controller-action">
 * <div> __Action__ _default_ </div>
 * <div>
 * Handle action according to HTTP method and parameters.
 *
 * For details about the paramters, see documentation for the methods
 * RESTController::handleGet(), RESTController::handlePost(),
 * RESTController::handlePut(), RESTController::handleDelete()
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
      !ObjectFactory::getInstance('persistenceFacade')->isKnownType($request->getValue('className')))
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
   * @see Controller::doExecute()
   */
  protected function doExecute() {
    $request = $this->getRequest();
    $response = $this->getResponse();
    switch ($request->getMethod()) {
      case 'GET':
        $this->handleGet();
        break;
      case 'POST':
        $this->handlePost();
        break;
      case 'PUT':
        $this->handlePut();
        break;
      case 'DELETE':
        $this->handleDelete();
        break;
      default:
        $this->handleGet();
        break;
    }
    $response->setAction('ok');
  }

  /**
   * Handle a GET request (read object(s) of a given type)
   *
   * | Parameter        | Description
   * |------------------|-------------------------
   * | _in_ `collection`| Boolean whether to load one object or a list of objects. The _Range_ header is used to get only part of the list
   * | _in_ `language`  | The language of the returned object(s)
   * | _in_ `className` | The type of returned object(s)
   * | _in_ `id`        | If collection is _false_, the object with _className_/_id_ will be loaded
   * | _in_ `sortBy`    | _?sortBy=+foo_ for sorting the list by foo ascending or
   * | _in_ `sort`      | _?sort(+foo)_ for sorting the list by foo ascending
   * | _in_ `limit`     | _?limit(10,25)_ for loading 25 objects starting from position 10
   * | _in_ `relation`  | Relation name if objects in relation to another object should be loaded (determines the type of the returned objects)
   * | _in_ `sourceId`  | Id of the object to which the returned objects are related (determines the object id together with _className_)
   * | _out_            | Single object or list of objects. In case of a list, the _Content-Range_ header will be set.
   *
   */
  protected function handleGet() {
    $request = $this->getRequest();
    $response = $this->getResponse();
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

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
        $response->setStatus(Response::STATUS_404);
      }

      // set the response headers
      //$this->setLocationHeaderFromOid($request->getValue('oid'));
    }
    else {
      // read all objects of the given type
      // delegate further processing to ListController
      $offset = 0;

      // parse headers

      // range
      if ($request->hasHeader('Range')) {
        if (preg_match('/^items=([\-]?[0-9]+)-([\-]?[0-9]+)$/', $request->getHeader('Range'), $matches)) {
          $offset = intval($matches[1]);
          $limit = intval($matches[2])-$offset+1;
          $request->setValue('offset', $offset);
          $request->setValue('limit', $limit);
        }
      }

      // parse get paramters
      foreach ($request->getValues() as $key => $value) {
        // sort definition
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
        // limit
        if (preg_match('/^limit\(([^\)]+)\)$/', $key, $matches)) {
          $rangeDefs = preg_split('/,/', $matches[1]);
          $limit = intval(array_pop($rangeDefs));
          $offset = sizeof($rangeDefs) > 0 ? intval($rangeDefs[0]) : 0;
          $request->setValue('offset', $offset);
          $request->setValue('limit', $limit);
          break;
        }
      }

      // create query from optional GET values
      if ($request->hasValue('className')) {
        $operatorMap = array('eq' => '=', 'ne' => '!=', 'lt' => '<', 'lte' => '<=',
            'gt' => '>', 'gte' => '>=', 'in' => 'in', 'match' => 'regexp');
        $mapper = $persistenceFacade->getMapper($request->getValue('className'));
        $type = $mapper->getType();
        $simpleType = $persistenceFacade->getSimpleType($type);
        $objectQuery = new ObjectQuery($type);
        foreach ($request->getValues() as $name => $value) {
          if (strpos($name, '.') > 0) {
            // check name for type.attribute
            list($typeInName, $attributeInName) = preg_split('/\.+(?=[^\.]+$)/', $name);
            if (($typeInName == $type || $typeInName == $simpleType) &&
                    $mapper->hasAttribute($attributeInName)) {
            $queryTemplate = $objectQuery->getObjectTemplate($type);
            // handle null values correctly
            $value = strtolower($value) == 'null' ? null : $value;
            // extract optional operator from value e.g. lt=2015-01-01
            $parts = explode('=', $value);
            $op = $parts[0];
            if (sizeof($parts) > 0 && isset($operatorMap[$op])) {
              $operator = $operatorMap[$op];
              $value = $parts[1];
            }
            else {
              $operator = '=';
            }
              $queryTemplate->setValue($attributeInName, Criteria::asValue($operator, $value));
            }
          }
        }
        $query = $objectQuery->getQueryCondition();
        if (strlen($query) > 0) {
          $request->setValue('query', $query);
        }
      }

      // rewrite query if querying for a relation
      if ($request->hasValue("relation") && $request->hasValue('sourceOid')) {
        $relationName = $request->getValue("relation");

        // set the query
        $sourceOid = ObjectId::parse($request->getValue('sourceOid'));
        $sourceNode = $persistenceFacade->load($sourceOid);
        if ($sourceNode) {
          $query = NodeUtil::getRelationQueryCondition($sourceNode, $relationName);
          $request->setValue('query', $query);

          // set the class name
          $mapper = $sourceNode->getMapper();
          $relation = $mapper->getRelation($relationName);
          $otherType = $relation->getOtherType();
          $request->setValue('className', $otherType);

          // set order
          $otherMapper = $persistenceFacade->getMapper($otherType);
          $sortkeyDef = $otherMapper->getSortkey($relation->getThisRole());
          if ($sortkeyDef != null) {
            $request->setValue('sortFieldName', $sortkeyDef['sortFieldName']);
            $request->setValue('sortDirection', $sortkeyDef['sortDirection']);
          }
        }
      }

      // execute action
      $subResponse = $this->executeSubAction('list');

      // return object list only
      $objects = $subResponse->getValue('list');
      $response->clearValues();
      $response->setValues($objects);

      // set response range header
      $size = sizeof($objects);
      $limit = $size == 0 ? $offset : $offset+$size-1;
      $total = $subResponse->getValue('totalCount');
      $response->setHeader('Content-Range', 'items '.$offset.'-'.$limit.'/'.$total);
    }
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
   * | Parameter        | Description
   * |------------------|-------------------------
   * | _in_ `language`  | The language of the object
   * | _in_ `className` | Type of object to create
   * | _in_ `relation`  | Relation name if the object should be created/added in relation to another object (determines the type of the created/added object)
   * | _in_ `sourceId`  | Id of the object to which the created objects are added (determines the object id together with _className_)
   * | _out_            | Created object data
   *
   * The object data is contained in POST content. If an existing object
   * should be added, an `oid` parameter in the object data is sufficient.
   */
  protected function handlePost() {
    $request = $this->getRequest();
    if ($request->hasValue('relation') && $request->hasValue('sourceOid')) {
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
      $response->setStatus(Response::STATUS_201);
      $this->setLocationHeaderFromOid($response->getValue('oid'));
    }
    else {
      // in case of error, return default response
      $response->setValues($subResponse->getValues());
      $response->setStatus(Response::STATUS_400);
    }
  }

  /**
   * Handle a PUT request (update an object of a given type)
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
  protected function handlePut() {
    $request = $this->getRequest();

    // check position header for reordering
    $orderReferenceOid = null;
    if ($request->hasHeader('Position')) {
      $position = $request->getHeader('Position');
      if ($position == 'last') {
        $orderReferenceOid = 'ORDER_BOTTOM';
      }
      else {
        list($ignore, $orderReferenceIdStr) = preg_split('/ /', $position);
        if ($request->hasValue('relation') && $request->hasValue('sourceOid')) {
          // sort in relation
          $sourceOid = ObjectId::parse($request->getValue('sourceOid'));
          $relatedType = $this->getRelatedType($sourceOid, $request->getValue('relation'));
          $orderReferenceOid = new ObjectId($relatedType, $orderReferenceIdStr);
        }
        else {
          // sort in root
          $orderReferenceOid = new ObjectId($request->getValue('className'), $orderReferenceIdStr);
        }
      }
    }

    if ($request->hasValue('relation') && $request->hasValue('sourceOid') &&
            $request->hasValue('targetOid')) {
      if ($orderReferenceOid != null) {
        // change order in a relation
        $request->setValue('containerOid', $request->getValue('sourceOid'));
        $request->setValue('insertOid', $request->getValue('targetOid'));
        $request->setValue('referenceOid', $orderReferenceOid);
        $request->setValue('role', $request->getValue('relation'));
        $subResponse = $this->executeSubAction('insertBefore');
      }
      else {
        // add existing object to relation
        $request->setValue('role', $request->getValue('relation'));
        $subResponse = $this->executeSubAction('associate');
        if ($subResponse->getStatus() == Response::STATUS_200) {
          // and update object
          $subResponse = $this->executeSubAction('update');
        }
      }

      // add related object to subresponse similar to default update action
      if ($subResponse->getStatus() == Response::STATUS_200) {
        $targetOidStr = $request->getValue('targetOid');
        $targetOid = ObjectId::parse($targetOidStr);
        $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
        $targetObj = $persistenceFacade->load($targetOid);
        $subResponse->setValue('oid', $targetOid);
        $subResponse->setValue($targetOidStr, $targetObj);
      }
    }
    else {
      if ($orderReferenceOid != null) {
        // change order in a relation
        $request->setValue('insertOid', $this->getFirstRequestOid());
        $request->setValue('referenceOid', $orderReferenceOid);
        $subResponse = $this->executeSubAction('moveBefore');

        // add sorted object to subresponse similar to default update action
        if ($subResponse->getStatus() == Response::STATUS_200) {
          $targetOidStr = $this->getFirstRequestOid();
          $targetOid = ObjectId::parse($targetOidStr);
          $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
          $targetObj = $persistenceFacade->load($targetOid);
          $subResponse->setValue('oid', $targetOid);
          $subResponse->setValue($targetOidStr, $targetObj);
        }
      }
      else {
        // update object
        $subResponse = $this->executeSubAction('update');
      }
    }
    $response = $this->getResponse();

    // in case of success, return object only
    $oidStr = $this->getFirstRequestOid();
    if (!$subResponse->hasErrors()) {
      if ($subResponse->hasValue($oidStr)) {
      $object = $subResponse->getValue($oidStr);
      $response->clearValues();
      $response->setValue($oidStr, $object);
        $this->setLocationHeaderFromOid($response->getValue('oid'));
      }
      $response->setStatus(Response::STATUS_202);
    }
    else {
      // in case of error, return default response
      $response->setValues($subResponse->getValues());
      $response->setStatus(Response::STATUS_400);
    }
  }

  /**
   * Handle a DELETE request (delete an object of a given type)
   *
   * | Parameter        | Description
   * |------------------|-------------------------
   * | _in_ `language`  | The language of the object
   * | _in_ `className` | Type of object to delete
   * | _in_ `id`        | Id of object to delete
   * | _in_ `relation`  | Relation name if the object should be deleted from a relation to another object (determines the type of the deleted object)
   * | _in_ `sourceId`  | Id of the object to which the deleted object is related (determines the object id together with _className_)
   * | _in_ `targetId`  | Id of the object to be deleted from the relation (determines the object id together with _relation_)
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

    if (!$subResponse->hasErrors()) {
      // set the response headers
      $response->setStatus(Response::STATUS_204);
    }
    else {
      // in case of error, return default response
      $response->setStatus(Response::STATUS_400);
    }
  }

  /**
   * Set the location response header according to the given object id
   * @param $oid The serialized object id
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
   * @param $sourceOid ObjectId of the source object
   * @param $role The role name
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
