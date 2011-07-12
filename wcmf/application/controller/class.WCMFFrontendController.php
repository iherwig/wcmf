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
 * - model: Get a list of template instances of all known model entities
 * - detail: Get a template instance of a given type and optional an object
 * - unspecified: Display the frontend
 *
 * <b>Output actions:</b>
 * - @em failure If a fatal error occurs
 * - @em ok In any other case
 *
 * @param[in] type The type to display get the instance for (action: detail)
 * @param[in] oid The object id of the node to read (action: detail)
 * @param[out] typeTemplate An instance of the requested type (action: detail)
 * @param[out] object The requested object loaded with BUILDDEPTH_SINGLE, if an oid is given, same as typeTemplate else (action: detail)
 * @param[out] typeTemplates A list of instances of all known types (action: model)
 * @param[out] isNew Boolean indicating if the object exists or not (action: model)
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
    if($request->getAction() == 'detail')
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
    $parser = InifileParser::getInstance();
    $request = $this->getRequest();
    $response = $this->getResponse();

    if ($request->getAction() == 'model')
    {
      // get all known types
      $knownTypes = $persistenceFacade->getKnownTypes();

      // get root types from ini file
      $rootTypes = $parser->getValue('rootTypes', 'cms');
      if ($rootTypes === false || !is_array($rootTypes) || $rootTypes[0] == '')
      {
        $this->setErrorMsg(Message::get("No root types defined."));
        $response->setAction('failure');
        return true;
      }

      // create type templates
      $typeTemplates = array();
      foreach ($knownTypes as $type)
      {
        if ($rightsManager->authorize($type, '', ACTION_READ))
        {
          // create the template
          $tpl = $persistenceFacade->create($type, BUILDDEPTH_SINGLE);

          // set properties used in views
          if (in_array($type, $rootTypes)) {
            $tpl->setProperty('isRootType', true);
          }
          else {
            $tpl->setProperty('isRootType', false);
          }

          // add the template
          $typeTemplates[] = $tpl;
        }
      }
      // set response values
      $response->setValue('typeTemplates', $typeTemplates);
    }
    else if ($request->getAction() == 'detail')
    {
      // called with type parameter
      if ($request->hasValue('type')) {
        $typeTemplate = $persistenceFacade->create($request->getValue('type'), BUILDDEPTH_SINGLE);
        $response->setValue('object', $typeTemplate);
        $response->setValue('typeTemplate', $typeTemplate);
        $response->setValue('isNew', true);
      }
      // called with oid parameter
      if ($request->hasValue('oid'))
      {
        $oid = ObjectId::parse($request->getValue('oid'));
        $typeTemplate = $persistenceFacade->create($oid->getType(), BUILDDEPTH_SINGLE);

        // call DisplayController to read the requested node
        // and merge the responses
        $readResponse = $this->executeSubAction('read', array(
            'oid' => $oid->__toString(),
            'depth' => 0
        ));
        $response->setValue('object', $readResponse->getValue('object'));
        $response->setValue('typeTemplate', $typeTemplate);
        $response->setValue('isNew', false);
      }
      $response->setValue('languages', $parser->getSection('languages'));
    }
    // success
    $response->setAction('ok');
    return false;
  }
}
?>
