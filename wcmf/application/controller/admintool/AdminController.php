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
 * $Id$
 */
namespace wcmf\application\controller\admintool;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\Controller;

/**
 * AdminController is used as an entry point to the admintool.
 *
 * <b>Input actions:</b>
 * - unspecified: Display the admintool start screen
 *
 * <b>Output actions:</b>
 * - @em ok In any case
 *
 * @param[out] users An array of all user instances
 * @param[out] roles An array of all role instances
 * @param[out] configfiles An array of all configuration file names
 * @param[out] mainconfigfile The name of the main configuration file
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class AdminController extends Controller {

  private $_userManager = null;

  /**
   * @see Controller::initialize()
   */
  public function initialize(&$request, &$response)
  {
    parent::initialize($request, $response);

    // create UserManager instance
    $this->_userManager = ObjectFactory::getInstance('userManager');
  }
  /**
   * @see Controller::validate()
   */
  function validate()
  {
    if($this->_userManager == null)
    {
      $this->setErrorMsg("No user manager defined.");
      return false;
    }
    return true;
  }
  /**
   * @see Controller::hasView()
   */
  function hasView()
  {
    return true;
  }
  /**
   * Process action and assign data to View.
   * @return False (Stop action processing chain).
   * @see Controller::executeKernel()
   */
  function executeKernel()
  {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $this->_userManager->beginTransaction();

    // assign model to view
    $userType = $this->_userManager->getUserClassName();
    $this->_response->setValue('userType', $userType);
    $this->_response->setValue('userTemplateNode', $persistenceFacade->create($userType, BuildDepth::REQUIRED));
    $roleType = $this->_userManager->getRoleClassName();
    $this->_response->setValue('roleType', $roleType);
    $this->_response->setValue('roleTemplateNode', $persistenceFacade->create($roleType, BuildDepth::REQUIRED));

    $this->_userManager->commitTransaction();

    // success
    $this->_response->setAction('ok');
    return false;
  }
}
?>
