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
require_once(BASE."wcmf/application/controller/class.PagingController.php");
require_once(BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(BASE."wcmf/lib/model/class.NodeUtil.php");

/**
 * @class NodeListController
 * @ingroup Controller
 * @brief NodeListController is a controller that loads nodes of a given type
 * and displays the result in a list.
 * 
 * <b>Input actions:</b>
 * - unspecified: List Nodes
 *
 * <b>Output actions:</b>
 * - see PagingController
 *
 * @param[in,out] type The type of node to list
 * @param[in,out] pageSize The number of nodes to display on one tab page (optional, default: 5)
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NodeListController extends PagingController
{
  /**
   * @see Controller::initialize()
   */
  function initialize(&$request, &$response)
  {
    parent::initialize($request, $response);

    // store the paging size in the session
    $pageSize = $this->getLength();
    if (intval($request->getValue('pageSize')) > 0 && $request->getValue('pageSize') != $pageSize)
    {
      $pageSize = $request->getValue('pageSize');
     	$session = &SessionData::getInstance();
      $session->set($this->getSessionPrefix().'pagesize', $pageSize);
      $this->reset();
    }
  }
  /**
   * @see Controller::validate()
   */
  function validate()
  {
    if(!PersistenceFacade::isKnownType($this->_request->getValue('type')))
    {
      $this->setErrorMsg("No valid 'type' given in data.");
      return false;
    }
    return parent::validate();
  }
  /**
   * @see PagingController::getOIDs()
   */
  function getOIDs()
  {
    $oids = array();
    
    $persistenceFacade = &PersistenceFacade::getInstance();
    $oids = &$persistenceFacade->getOIDs($this->_request->getValue('type'));

    return $oids;
  }
  /**
   * @see PagingController::getDisplayText()
   */
  function getDisplayText(&$node)
  {
    return strip_tags(preg_replace("/[\r\n']/", " ", NodeUtil::getDisplayValue($node)));
  }
  /**
   * @see PagingController::modifyModel()
   */
  function modifyModel(&$nodes)
  {
    NodeUtil::setSortProperties($nodes);
  }
  /**
   * @see PagingController::getLength()
   */
  function getLength()
  {
    // 1. return a value that is already stored in the session
    $sessionVarName = $this->getSessionPrefix().'pagesize';
    $session = &SessionData::getInstance();
    if ($session->exist($sessionVarName))
      return $session->get($sessionVarName);

    // 2. return the currently requested value
    if (intval($this->_request->getValue('pageSize')) > 0)
      $pageSize = $this->_request->getValue('pageSize');

    // 3. return the default value
    return 5;
  }
  /**
   * @see Controller::executeKernel()
   */
  function executeKernel()
  {
    $this->_response->setValue('type', $this->_request->getValue('type'));
    $this->_response->setValue('pageSize', $this->_request->getValue('pageSize'));

    return parent::executeKernel();
  }
  /**
   * Get the sesson varname prefix depending on parent and child type.
   * @return The prefix
   */
  function getSessionPrefix()
  {
    return 'NodeListController.'.$this->_request->getValue('type').'.';
  }
}
?>
