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
require_once(BASE."wcmf/application/controller/class.NodeListController.php");
require_once(BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(BASE."wcmf/lib/model/class.NodeUtil.php");
require_once(BASE."wcmf/lib/presentation/ListboxFunctions.php");

/**
 * @class ChildrenListController
 * @ingroup Controller
 * @brief ChildrenListController is a controller that loads chilren of a given node
 * and displays the result in a list.
 * 
 * <b>Input actions:</b>
 * - unspecified: List nodes
 *
 * <b>Output actions:</b>
 * - see NodeListController
 * 
 * @param[in,out] poid The object id of the parent node to list the children for
 * @param[in] type The entity type the type to list
 * @param[in,out] canCreate True/False wether children of that type may be created or not
 * @param[in,out] aggregation True/False wether children of that type are added as aggregation or not
 * @param[in,out] composition True/False wether children of that type are added as composition or not
 
 * For additional parameters see NodeListController
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ChildrenListController extends NodeListController
{
  /**
   * @see Controller::validate()
   */
  function validate()
  {
    if(!PersistenceFacade::isValidOID($this->_request->getValue('poid')))
    {
      $this->setErrorMsg("No valid 'poid' given in data.");
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
    $parent = &$persistenceFacade->load($this->_request->getValue('poid'), BUILDDEPTH_SINGLE);
    $type = $this->_request->getValue('type');

    $parent->loadChildren($type);
    $children = $parent->getChildrenEx(null, $type, null, null);
    for ($i=0; $i<sizeof($children); $i++)
      array_push($oids, $children[$i]->getOID());

    return $oids;
  }
  /**
   * @see PagingController::modifyModel()
   */
  function modifyModel(&$nodes)
  {
    parent::modifyModel(&$nodes);

    // as manyToMany objects act as a proxy, we set a property 'realSubject',
    // which holds to the real subject.
    $persistenceFacade = &PersistenceFacade::getInstance();
    for ($i=0; $i<sizeof($nodes); $i++)
    {
      $curNode = &$nodes[$i];
      if (in_array('manyToMany', $curNode->getPropertyNames()))
      {
        $realSubjectType = $this->getRealSubjectType(&$curNode);

        // get the real subject from the parentoids property
        $realSubject = null;
        foreach($curNode->getParentOIDs() as $curParentOID)
        {
          if (PersistenceFacade::getOIDParameter($curParentOID, 'type') == $realSubjectType)
          {
            $realSubject = &$persistenceFacade->load($curParentOID, BUILDDEPTH_SINGLE);
            break;
          }
        }

        $curNode->setProperty('realSubject', $realSubject);
      }
    }
  }
  /**
   * @see Controller::executeKernel()
   */
  function executeKernel()
  {
    // as manyToMany objects act as a proxy, we set a property 'subjectType',
    // which holds the type of the real subject and will be used instead of 'type'.
    $persistenceFacade = &PersistenceFacade::getInstance();
    $typeInstance = &$persistenceFacade->create($this->_request->getValue('type'), BUILDDEPTH_SINGLE);
    if (in_array('manyToMany', $typeInstance->getPropertyNames()))
      $this->_view->assign('subjectType', $this->getRealSubjectType(&$typeInstance));

    $this->_response->setValue('poid', $this->_request->getValue('poid'));
    $this->_response->setValue('canCreate', $this->_request->getValue('canCreate'));
    $this->_response->setValue('aggregation', $this->_request->getValue('aggregation'));
    $this->_response->setValue('composition', $this->_request->getValue('composition'));

    return parent::executeKernel();
  }
  /**
   * Get the sesson varname prefix depending on parent and child type.
   * @return The prefix
   */
  function getSessionPrefix()
  {
    return 'ChildrenListController.'.PersistenceFacade::getOIDParameter(
      $this->_request->getValue('poid'), 'type').'.'.$this->_request->getValue('type').'.';
  }
  /**
   * Get the real subject type for a proxy node.
   * @param node The proxy node
   * @return The type
   */
  function getRealSubjectType(&$node)
  {
    $parentType = PersistenceFacade::getOIDParameter($this->_request->getValue('poid'), 'type');

    // get the type of the real subject from the manyToMany property
    foreach($node->getProperty('manyToMany') as $curParentType)
    {
      if ($curParentType != $parentType)
        return $curParentType;
    }
    return null;
  }
}
?>
