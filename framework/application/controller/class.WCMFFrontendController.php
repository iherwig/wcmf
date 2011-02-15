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
 * $Id: class.WCMFFrontendController.php 1250 2010-12-05 23:02:43Z iherwig $
 */
require_once(WCMF_BASE."wcmf/lib/presentation/class.Controller.php");
require_once(WCMF_BASE."wcmf/lib/util/class.InifileParser.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/security/class.RightsManager.php");
require_once(WCMF_BASE."wcmf/lib/util/class.Log.php");

/**
 * @class WCMFFrontendController
 * @ingroup Controller
 * @brief WCMFFrontendController is used to display the wCMF frontend.
 *
 * <b>Input actions:</b>
 * - getModel: Get a list of template instances of all known model entities
 * - getDetail: Get a template instance of a given type and optional an object
 * - unspecified: Display the frontend
 *
 * <b>Output actions:</b>
 * - @em failure If a fatal error occurs
 * - @em ok In any other case
 *
 * @param[in] type The type to display get the instance for (action: node)
 * @param[in] oid The object id of the node to read (action: node)
 * @param[out] typeTemplate An instance of the requested type (action: node)
 * @param[out] object The requested object loaded with BUILDDEPTH_SINGLE, if an oid is given (action: node)
 * @param[out] typeTemplates A list of instances of all known types (action: model)
 * @param[out] rootTypeTemplates A list of instances of all root types
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class WCMFFrontendController extends Controller
{
  /**
   * @see Controller::validate()
   */
  protected function validate()
  {
    $request = $this->getRequest();
    $response = $this->getResponse();
    if($request->getAction() == 'getDetail')
    {
      if ($request->hasValue('type')) {
        $type = $request->getValue('type');
        if (!PersistenceFacade::getInstance()->isKnownType($type)) {
          $response->addError(ApplicationError::get('CLASS_NAME_INVALID'));
          return false;
        }
      }
      if ($request->hasValue('oid')) {
        $oid = ObjectId::parse($request->getValue('oid'));
        if (!$oid) {
          $response->addError(ApplicationError::get('OID_INVALID'));
          return false;
        }
      }
    }
    return true;
  }
  /**
   * @see Controller::executeKernel()
   */
  protected function executeKernel()
  {
    $persistenceFacade = PersistenceFacade::getInstance();
    $rightsManager = RightsManager::getInstance();
    $request = $this->getRequest();
    $response = $this->getResponse();

    if ($request->getAction() == 'getModel')
    {
      // get all known types
      $knownTypes = $persistenceFacade->getKnownTypes();

      // create type templates
      $typeTemplates = array();
      foreach ($knownTypes as $type) {
        $typeTemplates[] = $persistenceFacade->create($type, BUILDDEPTH_SINGLE);
      }
      // set response values
      $response->setValue('typeTemplates', $typeTemplates);
    }
    else if ($request->getAction() == 'getDetail')
    {
      // called with type parameter
      if ($request->hasValue('type')) {
        $typeTemplate = $persistenceFacade->create($request->getValue('type'), BUILDDEPTH_SINGLE);
        $response->setValue('object', $typeTemplate);
        $response->setValue('typeTemplate', $typeTemplate);
      }
      // called with oid parameter
      if ($request->hasValue('oid'))
      {
        $oid = ObjectId::parse($request->getValue('oid'));

        // call DisplayController to read the requested node
        // and merge the responses
        $readRequest = new Request('TerminateController', $request->getContext(), 'read');
        $readRequest->setValues(array(
            'oid' => $oid->__toString(),
            'depth' => 0,
            'sid' => SessionData::getInstance()->getID()
        ));
        $readRequest->setFormat('NULL');
        $readRequest->setResponseFormat('NULL');
        $readResponse = ActionMapper::getInstance()->processAction($readRequest);
        $response->setValue('object', $readResponse->getValue('object'));

        $typeTemplate = $persistenceFacade->create($oid->getType(), BUILDDEPTH_SINGLE);
        $response->setValue('typeTemplate', $typeTemplate);
      }
    }
    else
    {
      // get root types from ini file
      $parser = InifileParser::getInstance();
      $rootTypes = $parser->getValue('rootTypes', 'cms');
      if ($rootTypes === false || !is_array($rootTypes) || $rootTypes[0] == '')
      {
        $this->setErrorMsg(Message::get("No root types defined."));
        $response->setAction('failure');
        return true;
      }

      // create root type templates
      $rootTypeTemplates = array();
      foreach ($rootTypes as $rootType)
      {
        if ($rightsManager->authorize($rootType, '', ACTION_READ)) {
          $rootTypeTemplates[] = $persistenceFacade->create($rootType, BUILDDEPTH_SINGLE);
        }
      }
      // set response values
      $response->setValue('rootTypeTemplates', $rootTypeTemplates);
    }
    // success
    $response->setAction('ok');
    return false;
  }
}
?>
