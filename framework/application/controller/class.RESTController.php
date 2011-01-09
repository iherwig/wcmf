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
    if (!$request->hasValue('className') || 
      !PersistenceFacade::getInstance()->isKnownType($request->getValue('className')))
    {
      $this->addError(ApplicationError::get('PARAMETER_INVALID', 
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
    
    switch ($method)
    {
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
    return true;
  }
  /**
   * Handle a GET request (read object(s) of a given type)
   */
  protected function handleGet()
  {
    $request = $this->getRequest();
    $response = $this->getResponse();
    $type = $request->getValue('className');
    
    if ($request->hasValue('id')) {
      // read a specific object
      // delegate further processing to DisplayController
      $oid = new ObjectId($type, $request->getValue('id'));
      $response->setValue('oid', $oid->__toString());
      $response->setAction('display');
    }
    else {
      // read all objects of the given type
      // delegate further processing to AsyncPagingController
      $response->setValue('className', $type);
      $response->setAction('list');
    }
    return true;
  }
  /**
   * Handle a POST request (update an object of a given type)
   */
  protected function handlePost()
  {
  }
  /**
   * Handle a PUT request (create an object of a given type)
   */
  protected function handlePut()
  {
  }
  /**
   * Handle a DELETE request (delete an object of a given type)
   */
  protected function handleDelete()
  {
  }
}
?>
