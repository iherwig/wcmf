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
require_once(BASE."wcmf/3rdparty/PhpXmlDb/xmldb.php");
require_once(BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(BASE."wcmf/lib/core/class.WCMFException.php");

function XMLUtilErrorHandler($errno, $errstr, $errfile, $errline)
{
  switch ($errno) 
  {
    case E_USER_NOTICE: 
       WCMFException::throwEx($errstr, $errfile, $errline);
       break; 
    default:
       break; 
  } 
}
define("ROOT_NODE_NAME", 'XmlDatabase');

/**
 * @class XMLUtil
 * @ingroup Util
 * @brief XMLUtil helps in using XML files as storage.
 * XMLUtil is a subclass of CXmlDb that is customized for use with the wemove cms framework.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class XMLUtil extends CXmlDb
{
  var $_errorMsg = '';
  var $_idName = 'id';
  
  /**
   * Get last error message.
   * @return The error string
   */
  function getErrorMsg()
  {
    return $this->_errorMsg;
  }
  /**
   * Get the root node name.
   * @return The root node name
   */
  function getRootNodeName()
  {
    return ROOT_NODE_NAME;
  }
  /**
   * Get the object id of the root node.
   * @return The object id of the root node
   */
  function getRootOID()
  {
    return PersistenceFacade::composeOID(array('type' => ROOT_NODE_NAME, 'id' => array('')));
  }
	/**
	 * Load Node data.
	 *
   * @param oid The OID of the Node to load the data for.
   * @param elementName The name of the DATATYPE_ELEMENT field of the Node (content will be mapped here).
   * @return An assoziative array holding key value pairs of all Node data (attributes and content)
   *         0 on failure / error string provided by getErrorMsg()
	 */
  function GetNodeData($oid, $elementName) 
	{
    $old_error_handler = set_error_handler("XMLUtilErrorHandler"); 
		$iResult = 0;

    if ($this->_CheckAction('GetNodeData'))
    {
  		// Call the internal version of the function.
  		$iResult = $this->_GetNodeData($oid, $elementName);
    }
    
    set_error_handler($old_error_handler);
		return $iResult;
	}
	/**
	 * Load Node child data.
	 *
	 * @param oid The OID of the Node to load the child data for.
	 * @return An array holding assoziative arrays with key value pairs of child data (keys 'type', 'id')
	 *         0 on failure / error string provided by getErrorMsg()
	 */
  function GetChildData($oid) 
	{
    $old_error_handler = set_error_handler("XMLUtilErrorHandler"); 
		$iResult = 0;

    if ($this->_CheckAction('GetChildData'))
    {
  		// Call the internal version of the function.
  		$iResult = $this->_GetChildData($oid);
    }
    
    set_error_handler($old_error_handler);
		return $iResult;
	}
  /**
   * Get Node OID from a XPath query.
   *
   * @param nodeQuery The XPath query.
   * @return Array of OIDs on success, 0 on failure / error string provided by getErrorMsg()
   */
  function GetOIDs($nodeQuery)
  {
    $oids = array();
    $nodePathArray = $this->XmlDb->evaluate($nodeQuery);
    foreach ($nodePathArray as $nodePath)
      array_push($oids, $this->_GetOID($nodePath));
    return $oids;
  }
	/**
	 * Add a new Node to the Node with given parentOID.
	 *
	 * @param node	The Node to add
	 * @param parentOID The OID of the parent Node [maybe null to add to root].
	 * @return The ID of the new Node that was added on success, 0 on failure / error string provided by getErrorMsg()
	 */
  function InsertNode(&$node, $parentOID) 
  {
    $old_error_handler = set_error_handler("XMLUtilErrorHandler"); 
    $iResult = 0;

    if ($this->_CheckAction('InsertNode'))
    {
      // Call the internal version of the function.
      $iResult = $this->_InsertNode($node, $parentOID);
  
      // If node was added, set the Modify Flag to TRUE
      if ($iResult) 
        $this->bModifyFlag = true;
    }

    set_error_handler($old_error_handler);
    return $iResult;
  }
  /**
   * Save a Node to the XmlDb.
   *
   * @param node  The Node to save
   * @return 1 on success, 0 on failure / error string provided by getErrorMsg()
   */
  function UpdateNode(&$node) 
  {
    $old_error_handler = set_error_handler("XMLUtilErrorHandler"); 
    $iResult = 0;

    if ($this->_CheckAction('UpdateNode'))
    {
      // Call the internal version of the function.
      $iResult = $this->_UpdateNode($node);
  
      // If node was added, set the Modify Flag to TRUE
      if ($iResult) 
        $this->bModifyFlag = true;
    }

    set_error_handler($old_error_handler);
    return $iResult;
  }
  /**
   * Remove a Node from the XmlDb.
   *
   * @param oid The OID of the Node to remove
   * @return 1 on success, 0 on failure / error string provided by getErrorMsg()
   */
  function RemoveNode($oid) 
  {
    $old_error_handler = set_error_handler("XMLUtilErrorHandler"); 
    $iResult = 0;

    if ($this->_CheckAction('RemoveNode'))
    {
      // Call the internal version of the function.
      $iResult = $this->_RemoveNode($oid);
  
      // If node was added, set the Modify Flag to TRUE
      if ($iResult) 
        $this->bModifyFlag = true;
    }

    set_error_handler($old_error_handler);
    return $iResult;
  }
  /**
   * Get the next record id.
   *
   * @return The next ID for insertion
   */
  function GetNextInsertId()
  {
    $nextID = 1;

    $idTable = 'nextID';
    $idTablePath = '/*/'.$idTable;

    $idPaths = $this->XmlDb->evaluate($idTablePath);
    if (sizeof($idPaths) == 0)
    {
      // add id table
      $rootPathArray = $this->XmlDb->evaluate("/*");
      $newID = $nextID+1;
      $idNode = "<$idTable>$newID</$idTable>";
      $this->XmlDb->appendChild($rootPathArray[0], $idNode);
    }
    else
    {
      // get next id
      $nextID = intval($this->XmlDb->getData($idTablePath));
      $newID = $nextID+1;
      $this->XmlDb->replaceData($idPaths[0], $newID);
    }
    return $nextID;
  }


  /**
   * Internal version of GetNodeData.
   *
   * @param oid The OID of the Node to load the data for.
   * @param elementName The name of the DATATYPE_ELEMENT field of the Node (content will be mapped here).
   * @return An assoziative array holding key value pairs of all Node data (attributes and content)
   *         0 on failure / error string provided by getErrorMsg()
   */
  function _GetNodeData($oid, $elementName)
  {
    $iResult = 0;

    // get the node path
    $nodePath = $this->_GetNodePath($oid);
    if ($nodePath != '')
    {
      // get the attributes
      $iResult = $this->XmlDb->getAttributes($nodePath);
      // get the element
      $iResult[$elementName] = $this->XmlDb->getData($nodePath);
      // get ptype and pid
      $parentPath = $this->XmlDb->getParentXPath($nodePath);
      $iResult['ptype'] = array_pop($this->_GetTypes($parentPath));
      $parentAttributes = $this->XmlDb->getAttributes($parentPath);
      $iResult['pid'] = $parentAttributes[$this->_idName];
    }
    return $iResult;
  }
  /**
   * Internal version of GetChildData.
   *
   * @param oid The OID of the Node to load the child data for.
   * @return An array holding assoziative arrays with key value pairs of child data (keys 'type', 'id')
   *         0 on failure / error string provided by getErrorMsg()
   */
  function _GetChildData($oid) 
  {
    $iResult = 0;

    // get the node path
    $nodePath = $this->_GetNodePath($oid);
    if ($nodePath != '')
    {
      $iResult = array();
      // get the attributes
      $childrenPathArray = $this->XmlDb->evaluate($nodePath.'/*');
      foreach($childrenPathArray as $childPath)
      {
        // get type and id of child
        $childType = array_pop($this->_GetTypes($childPath));
        $childAttributes = $this->XmlDb->getAttributes($childPath);
        $childId = $childAttributes[$this->_idName];
        array_push($iResult, array('type' => $childType, 'id' => $childId));
      }
    }
    return $iResult;
  }
  /**
   * Internal version of InsertNode.
   *
   * @param node  The Node to add
   * @param parentOID The OID of the parent Node [maybe null to add to root].
   * @return The ID of the new Node that was added on success, 0 on failure / error string provided by getErrorMsg()
   */
  function _InsertNode(&$node, $parentOID) 
  {
    $iResult = 0;

    // get the parent path
    if ($parentOID != null)
      $parentPath = $this->_GetNodePath($parentOID);
    else
      $parentPath = $this->_GetNodePath($this->getRootOID());;
    
    // get the new id
    $newId = $this->GetNextInsertId();
    
    // define element content
    $nodeContent = '';
    $elementNames = $node->getValueNames(DATATYPE_ELEMENT);
    foreach($elementNames as $elementName)
      $nodeContent .= $node->getValue($elementName, DATATYPE_ELEMENT);
    
    // define element
    $nodeDef = PersistenceFacade::decomposeOID($node->getOID());
    $nodeString = "<".$nodeDef['type'].">".$nodeContent."</".$nodeDef['type'].">";
    
    // add node
    $nodePath = $this->XmlDb->appendChild($parentPath, $nodeString);

    // define attributes
    $addAttributes = array();
    $addAttributes[$this->_idName] = $newId;    
    $attributeNames = $node->getValueNames(DATATYPE_ATTRIBUTE);
    foreach($attributeNames as $attributeName)
      $addAttributes[$attributeName] = $node->getValue($attributeName, DATATYPE_ATTRIBUTE);

    // set attributes
    $this->XmlDb->setAttributes($nodePath, $addAttributes);

    // set the new id on the node
    $node->setOID(PersistenceFacade::composeOID(array('type' => $node->getType(), 'id' => $newId)));

    $iResult = $newId;
    return $iResult;
  }
  /**
   * Internal version of UpdateNode.
   *
   * @param node  The Node to save
   * @return 1 on success, 0 on failure / error string provided by getErrorMsg()
   */
  function _UpdateNode(&$node) 
  {
    $iResult = 0;

    // get the node path (take the first matching node)
    $nodePath = $this->_GetNodePath($node->getOID());
    
    // define element content
    $nodeContent = '';
    $elementNames = $node->getValueNames(DATATYPE_ELEMENT);
    foreach($elementNames as $elementName)
      $nodeContent .= $node->getValue($elementName, DATATYPE_ELEMENT);
    
    // replace node
    $this->XmlDb->replaceData($nodePath, $nodeContent);

    // define attributes
    $addAttributes = array();
    $attributeNames = $node->getValueNames(DATATYPE_ATTRIBUTE);
    foreach($attributeNames as $attributeName)
      $addAttributes[$attributeName] = $node->getValue($attributeName, DATATYPE_ATTRIBUTE);

    // set attributes
    $this->XmlDb->setAttributes($nodePath, $addAttributes);

    return 1;
  }
  /**
   * Internal version of RemoveNode.
   *
   * @param oid The OID of the Node to remove
   * @return 1 on success, 0 on failure / error string provided by getErrorMsg()
   */
  function _RemoveNode($oid) 
  {
    // remove node
    $this->_RemoveRecord(array($this->_GetNodePath($oid)));

    return 1;
  }
  /**
   * Check if execution of an action is possible.
   *
   * @param action The action to execute
   * @return True/False whether execution is possible
   */
  function _CheckAction($action)
  {
    if (!$this->_FunctionPermitted($action))
    {
      $this->_errorMsg = 'The function call is not allowed.';
      return false;
    }
    if (!$this->bFileOpen) 
    {
      $this->_errorMsg = 'The Open call did not open the file successfully.';
      return false;
    }
    if (!$this->bWriteAccess) 
    {
      $this->_errorMsg = 'To alter the database, you need to open it with write access.';
      return false;
    }

    return true;
  }
  /**
   * Get the path to a Node.
   *
   * @param oid The OID of the Node
   * @return The path to the Node
   */
  function _GetNodePath($oid)
  {
    // get the node path (take the first matching node)
    $nodeDef = PersistenceFacade::decomposeOID($oid);
    
    // the root node has no id
    if ($nodeDef['type'] == $this->getRootNodeName())
      $nodeQuery = "descendant::".$nodeDef['type'];
    else
      $nodeQuery = "descendant::".$nodeDef['type']."[@".$this->_idName."='".$nodeDef['id']."']";
      
    $nodePathArray = $this->XmlDb->evaluate($nodeQuery);
    return $nodePathArray[0];
  }
  /**
   * Get the Node types contained in a path.
   *
   * @param path The path to get the types for
   * @return An array containing the types sorted from parent to children
   */
  function _GetTypes($path)
  {
    preg_match_all('/\/(.+)\[[0-9]+\]/U', $path, $matches);
    return $matches[1];
  }
  /**
   * Get Node OID from a path.
   *
   * @param nodePath The path to the Node
   * @return OID on success, 0 on failure / error string provided by getErrorMsg()
   */
  function _GetOID($nodePath) 
  {
    $old_error_handler = set_error_handler("XMLUtilErrorHandler"); 
    $iResult = 0;

    // get type and id
    $type = array_pop($this->_GetTypes($nodePath));
    $nodeAttributes = $this->XmlDb->getAttributes($nodePath);
    $id = $nodeAttributes[$this->_idName];
    $iResult = PersistenceFacade::composeOID(array('type' => $type, 'id' => $id));

    set_error_handler($old_error_handler);
    return $iResult;
  }
}
?>
