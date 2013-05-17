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
 * @param [in] className The type of the object(s) to perform the action on
 * @param [in] id The id of the object to perform the action on [optional]
 * @param [out] ...
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
      if ($request->hasValue("relation")) {
        
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
   * Handle a POST request (create/update an object of a given type)
   */
  protected function handlePost() {
    $subResponse = $this->executeSubAction('create');
    $response = $this->getResponse();

    // in case of success, return object only
    $oidStr = $subResponse->hasValue('oid') ? $subResponse->getValue('oid')->__toString() : '';
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
    $response->setStatus('201 Created');
    $this->setLocationHeaderFromOid($response->getValue('oid'));

    return false;
  }

  /**
   * Handle a PUT request (create/update an object of a given type)
   */
  protected function handlePut() {
    $subResponse = $this->executeSubAction('update');
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
   */
  protected function handleDelete() {
    $subResponse = $this->executeSubAction('delete');

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
}
?>
