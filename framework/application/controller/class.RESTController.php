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
require_once(WCMF_BASE."wcmf/lib/presentation/class.Controller.php");

/**
 * @class RESTController
 * @ingroup Controller
 * @brief RESTController is a controller that handles REST requests.
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
class RESTController extends Controller
{
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
  function validate()
  {
    $request = $this->getRequest();
    $response = $this->getResponse();
    if ($request->hasValue('className') &&
      !PersistenceFacade::getInstance()->isKnownType($request->getValue('className')))
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
  public function executeKernel()
  {
    $method = $_SERVER['REQUEST_METHOD'];
    $result = false;
    switch ($method)
    {
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
  protected function handleGet()
  {
    $request = $this->getRequest();
    $response = $this->getResponse();

    // construct oid from className and id
    if ($request->hasValue('className') && $request->hasValue('id')) {
      $oid = new ObjectId($request->getValue('className'), $request->hasValue('id'));
      $request->setValue('oid', $oid->__toString());
    }

    if ($request->hasValue('oid')) {
      // read a specific object
      // delegate further processing to DisplayController
      $subResponse = $this->executeSubAction('read');
      $response->setValues($subResponse->getValues());

      // set the response headers
      header("HTTP/1.1 200 OK");
      //$this->setLocationHeaderFromOid($request->getValue('oid'));
    }
    else {
      // read all objects of the given type
      // delegate further processing to AsyncPagingController
      $subResponse = $this->executeSubAction('list');
      $response->setValues($subResponse->getValues());
    }

    return false;
  }
  /**
   * Handle a POST request (create/update an object of a given type)
   */
  protected function handlePost()
  {
    $subResponse = $this->executeSubAction('create');

    $response = $this->getResponse();
    $response->setValues($subResponse->getValues());

    // set the response headers
    header("HTTP/1.1 201 Created");
    $this->setLocationHeaderFromOid($response->getValue('oid'));

    return false;
  }
  /**
   * Handle a PUT request (create/update an object of a given type)
   */
  protected function handlePut()
  {
    $subResponse = $this->executeSubAction('update');

    $response = $this->getResponse();
    $response->setValues($subResponse->getValues());

    // set the response headers
    header("HTTP/1.1 200 OK");
    $this->setLocationHeaderFromOid($response->getValue('oid'));

    return false;
  }
  /**
   * Handle a DELETE request (delete an object of a given type)
   */
  protected function handleDelete()
  {
    $subResponse = $this->executeSubAction('delete');

    $response = $this->getResponse();
    $response->setValues($subResponse->getValues());

    // set the response headers
    header("HTTP/1.1 204 No Content");

    return false;
  }
  /**
   * Set the location response header according to the given object id
   * @param oid The serialized object id
   */
  protected function setLocationHeaderFromOid($oid)
  {
    $oid = ObjectId::parse($oid);
    if ($oid) {
      header("Location: ".$oid->__toString());
    }
  }
}
?>
