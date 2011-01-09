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
 * - unspecified: Display the frontend
 * - model: Get a list of template instances of all known model entities
 *
 * <b>Output actions:</b>
 * - @em failure If a fatal error occurs
 * - @em ok In any other case
 *
 * @param[out] rootNodeTemplates A list of instances of all root types
 * @param[out] nodeTemplates A list of instances of all known types (action: model)
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class WCMFFrontendController extends Controller
{
  /**
   * @see Controller::executeKernel()
   */
  protected function executeKernel()
  {
    $persistenceFacade = PersistenceFacade::getInstance();
    $rightsManager = RightsManager::getInstance();
    $request = $this->getRequest();
    $response = $this->getResponse();
    
    if ($request->getAction() == 'model')
    {
      // get all known types
      $knownTypes = $persistenceFacade->getKnownTypes();

      // create type templates
      $nodeTemplates = array();
      foreach ($knownTypes as $type) {
        $nodeTemplates[] = $persistenceFacade->create($type, BUILDDEPTH_SINGLE);
      }
      // set response values
      $response->setValue('nodeTemplates', $nodeTemplates);
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
      $rootNodeTemplates = array();
      foreach ($rootTypes as $rootType)
      {
        if ($rightsManager->authorize($rootType, '', ACTION_READ)) {
          $rootNodeTemplates[] = $persistenceFacade->create($rootType, BUILDDEPTH_SINGLE);
        }
      }
      // set response values
      $response->setValue('rootNodeTemplates', $rootNodeTemplates);
    }
    // success
    $response->setAction('ok');
    return false;
  }
}
?>
