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
require_once(BASE."wcmf/lib/model/class.Node.php");
require_once(BASE."wcmf/lib/util/class.Message.php");
require_once(BASE."wcmf/lib/presentation/class.Controller.php");
require_once(BASE."wcmf/lib/util/class.StringUtil.php");
require_once(BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(BASE."wcmf/lib/security/class.RightsManager.php");

/**
 * @class TreeViewController
 * @ingroup Controller
 * @brief TreeViewController is used to visualize cms data in a tree view.
 *
 * <b>Input actions:</b>
 * - @em loadChilren Load the children of the given parent Node
 *
 * <b>Output actions:</b>
 * - @em ok In any case
 *
 * @param[in] node The object id of the parent Node whose children should be loaded
 * @param[in] sort The attribute to sort the children by
 * @param[out] objects An array of associative arrays with keys 'oid', 'text', 'onClickAction', 'hasChildren'
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class TreeViewController extends Controller
{
  /**
   * @see Controller::hasView()
   */
  function hasView()
  {
    if ($this->_request->getAction() == 'loadChildren') {
      return false;
    }
    else {
      return true;
    }
  }
  /**
   * Assign data to View.
   * @return Array of given context and action 'failure' on failure.
   *         False on success (Stop action processing chain).
   *         In case of 'failure' a detailed description is provided by getErrorMsg().
   * @see Controller::executeKernel()
   */
  function executeKernel()
  {
    // the tree component sends the object id of the parent node in the 'node' parameter
    $oid = $this->_request->getValue('node');
  
    if ($this->_request->getAction() == 'loadChildren')
    {
      // load model
      $nodes = $this->getChildren($oid);
      
      // translate all nodes to the requested language if requested
      if ($this->isLocalizedRequest())
      {
        $localization = Localization::getInstance();
        for ($i=0; $i<sizeof($nodes); $i++) {
          $localization->loadTranslation($nodes[$i], $this->_request->getValue('language'), true, true);
        }
      }
      
      // sort nodes if requested
      if ($this->_request->hasValue('sort')) {
        Node::sort($nodes, $this->_request->getValue('sort'));
      }
      
      // create the json response
      $responseObjects = array();
      for($i=0; $i<sizeof($nodes); $i++)
      {
        $node = &$nodes[$i];
        if ($this->isVisible($node)) {
          array_push($responseObjects, $this->getViewNode($node));
        }
      }
      $this->_response->setValue('objects', $responseObjects);
    }
    
    // success
    $this->_response->setAction('ok');
    return false;
  }
  /**
   * Get the OIDs of the root nodes. TreeViewController will build the complete
   * resource tree from these.
   * @note subclasses will override this to implement special application requirements.
   * @return An array of OIDs.
   */
  function getRootOIDs()
  {
    $oids = array();
    
    // get root types from ini file
    $parser = &InifileParser::getInstance();
    $rootTypes = $parser->getValue('rootTypes', 'cms');
    if ($rootTypes === false || !is_array($rootTypes) ||  $rootTypes[0] == '') {
      $this->setErrorMsg("No root types defined.");
    }
    else
    {
      $persistenceFacade = &PersistenceFacade::getInstance();
      foreach($rootTypes as $rootType) {
        $oids = array_merge($oids, $persistenceFacade->getOIDs($rootType));
      }
    }
    return $oids;
  }
  /**
   * Get the children for a given oid.
   * @note subclasses will override this to implement special application requirements.
   * @return An array of Node instances.
   */
  function getChildren($oid)
  {
    $persistenceFacade = &PersistenceFacade::getInstance();
    $rightsManager = &RightsManager::getInstance();

    $nodes = array();
    if ($oid != 'root' && PersistenceFacade::isValidOID($oid))
    {
      // load children
      if ($rightsManager->authorize($oid, '', ACTION_READ))
      {
        $parentNode = &$persistenceFacade->load($oid, 1);
        $nodes = $parentNode->getChildren();
      }
    }
    else
    {
      // first call or reload
      $rootOIDs = $this->getRootOIDs();
      foreach ($rootOIDs as $rootOID)
      {
        if ($rightsManager->authorize($rootOID, '', ACTION_READ))
        {
          $node = &$persistenceFacade->load($rootOID, BUILDDEPTH_SINGLE);
          $nodes[sizeof($nodes)] = &$node;
        }
      }
    }
    return $nodes;
  }
  /**
   * Get the view of a Node
   * @param node The Node to create the view for
   * @param displayText The text to display (will be taken from TreeViewController::getDisplayText() if not specified) [default: '']
   * @return An associative array whose keys correspond to Ext.tree.TreeNode config parameters
   */
  function getViewNode(&$node, $displayText='')
  {
    if (strlen($displayText) == 0) {
      $displayText = trim($this->getDisplayText($node));
    }
    if (strlen($displayText) == 0) {
      $displayText = '-';
    }
    // click action    
    $onClickAction = $this->getClickAction($node);
    if ($onClickAction == null) {
      $onClickAction = '#';
    }
    $hasChildren = sizeof($node->getProperty('childoids')) > 0;
    return array('oid' => $node->getOID(), 'text' => $displayText, 'onClickAction' => $onClickAction,
      'hasChildren' => $hasChildren);
  }
  /**
   * Test if a Node should be displayed in the tree
   * @note subclasses will override this to implement special application requirements.
   * @param node A reference to the Node to display
   * @return True/false (the default implementation always returns true)
   */
  function isVisible(&$node)
  {
    return true;
  }
  /**
   * Get the display text for a Node
   * @note subclasses will override this to implement special application requirements.
   * @param node A reference to the Node to display
   * @return The display text.
   */
  function getDisplayText(&$node)
  {
    return strip_tags(preg_replace("/[\r\n']/", " ", NodeUtil::getDisplayValue($node)));
  }
  /**
   * Get the action to perform if a Node is clicked (The content of the anchor tag href attribute)
   * @note subclasses will override this to implement special application requirements.
   * @param node A reference to the Node
   * @return The action
   */
  function getClickAction(&$node)
  {
    //return "setTarget('nodeview'); doDisplay('".$node->getOID()."'); submitAction('display'); return false";
    return "javascript:opener.setContext('".$node->getType()."'); opener.doDisplay('".$node->getOID()."'); opener.submitAction('display');";
  }
}
?>
