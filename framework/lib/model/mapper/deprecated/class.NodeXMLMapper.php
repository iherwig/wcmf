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
require_once(BASE."wcmf/lib/core/class.WCMFException.php");
require_once(BASE."wcmf/lib/persistence/class.PersistenceMapper.php");
require_once(BASE."wcmf/lib/model/class.Node.php");  
require_once(BASE."wcmf/lib/model/class.NodeIterator.php");  
require_once(BASE."wcmf/lib/visitor/class.OutputVisitor.php");  
require_once(BASE."wcmf/lib/output/class.XMLOutputStrategy.php");  

/**
 * Some constants describing the data types
 */
define("DATATYPE_DONTCARE",  0);
define("DATATYPE_ATTRIBUTE", 1);
define("DATATYPE_ELEMENT",   2);
define("DATATYPE_IGNORE",    3); // all data items >= DATATYPE_IGNORE wont be shown in human readable node discriptions
/**
 * Some constants describing the build process
 */
define("BUILDDEPTH_INFINITE", -1); // build complete tree from given root on
define("BUILDDEPTH_SINGLE",   -2); // build only given node
define("BUILDDEPTH_GROUPED",  -3); // build tree from given root on respecting the root property defined in element relations
                                   // NODE: BUILDDEPTH_GROUPED is not supported yet!

define("NEXTID_NODE", 2);  // the id of the node that holds the next insert id

/**
 * @class NodeXMLMapper
 * @ingroup Mapper
 * @brief NodeXMLMapper maps nodes to a xml file.
 * @deprecated Use NodeXMLDBMapper instead
 *
 * @todo: add DataConverter, Logging
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NodeXMLMapper extends PersistenceMapper
{  
  // xml file variables
  var $_filename = '';
  var $_doctype = '';
  var $_dtd = '';
  // build process variables
  var $_root = null;       // the root node of the built tree
  var $_prevId = -1;       // the id of the previously read node
  var $_curParent = null;  // the parent node where the current built node will be added to
  var $_curNode = null;    // the current node in build process
  var $_startId = null;    // the id we start at for loading
  var $_buildDepth = 0;    // the depth of the tree tor build
  var $_curDepth = -1;     // the current depth in the xml file tree
  var $_startDepth = -1;   // the depth of the root node of the built tree in the xml file tree
  var $_saveTree = null;   // the temporary tree that will be modified during a transaction and saved afterwards
  // state variables
  var $_loading = false;   // indicates that we are inside the node that we are loading
  var $_intransaction = false;  // indicates that we are inside a transaction
  
  /**
   * Constructor.
   * @param params Initialization data given in an assoziative array with the following keys: 
   * filename, doctype, dtd
   */
  function NodeXMLMapper($params)
  {
    $this->_filename = $params['filename'];
    $this->_doctype  = $params['doctype'];
    $this->_dtd      = $params['dtd'];
  }
  /**
   * Construct a Node from the xml file.
   * @param oid Id of the Node to construct
   * @param buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   * @return A reference to the Node, null if oid does not exist.
   */
  function &load($oid, $buildDepth)
  {
    $this->_root = null;
    $this->_prevId = -1;
    $this->_curParent = null;
    $this->_curNode = null;
    $this->_startId = $oid;
    if ($buildDepth == BUILDDEPTH_SINGLE)
      $buildDepth = 0;
    $this->_buildDepth = $buildDepth;
    $this->_curDepth = -1;
    $this->_startDepth = -1;
    $this->_loading = false;
    $this->_intransaction = false;
      
    // setup xml parser
    $xml_parser = xml_parser_create();
    xml_set_object($xml_parser,&$this);
    xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);
    xml_set_element_handler($xml_parser, "parseStartTag", "parseEndTag");
    xml_set_character_data_handler($xml_parser, "parseElement");
    if (!($fp = fopen($this->_filename, "r")))
      WCMFException::throwEx("Could not open XML input: ".$this->_filename, __FILE__, __LINE__);

    // parse xml
    while ($data = fread($fp, 4096))
      if (!xml_parse($xml_parser, $data, feof($fp)))
        WCMFException::throwEx("XML error: ".xml_error_string(xml_get_error_code($xml_parser))." at line ".xml_get_current_line_number($xml_parser), __FILE__, __LINE__);

    xml_parser_free($xml_parser);    

    // return built tree
    if ($this->_root != null)
      $this->_root->setState(STATE_CLEAN);
    return $this->_root;
  }
  /**
   * Construct the template of a Node (defined by element name).
   * @param type The element's type
   * @param buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        @note BUILDDEPTH is not supported yet!
   * @return A reference to the Node.
   */
  function &create($type, $buildDepth)
  {
    // NODE: The creation is hardcoded by now.
    //       It should be done using a dtd and supplement specifications for the node (CMS) properties in future.
    $node = new Node($type);
    $node->setMapper($this);
    
    // set attributes / element
    if ($type == 'news')
      $node->setValue('index', '', DATATYPE_ATTRIBUTE);
      
    else if ($type == 'date')
      $node->setValue('date', '', DATATYPE_ELEMENT);
      
    else if ($type == 'headline')
      $node->setValue('headline', '', DATATYPE_ELEMENT);
      
    else if ($type == 'textblock')
      $node->setValue('textblock', '', DATATYPE_ELEMENT);

    else if ($type == 'image')
    {
      $node->setValue('src', '', DATATYPE_ATTRIBUTE);
      $node->setValue('width', '', DATATYPE_ATTRIBUTE);
      $node->setValue('height', '', DATATYPE_ATTRIBUTE);
    }
    
    return $node;
  }
  /**
   * Save a Node to the xml file (inserted if it is new).
   * @param node A reference to the Node to safe
   * @return True/False depending on success of operation
   */
  function save(&$node)
  {
    // NODE: changes will be done to the savetree, which is built by a call to startTransaction()
    // these changes will be made permanent by a call to commitTransaction().
    
    if ($this->_saveTree == null)
      return false;

    $saveIter = new NodeIterator($this->_saveTree);
    if ($node->getState() == STATE_NEW)
    {
      // insert new node
      // precondition: the node has a parent and its id exists in the database
      $parent = &$node->getParent();
      if ($parent != null)
      {
        // save node by adding into the tree
        // set new insert id
        $nextId = $this->getNextInsertId();
        $node->setOID($nextId);
        // search for parent and add node to it
        while(!$saveIter->isEnd())
        {
          $currentNode = &$saveIter->getCurrentObject();
          if ($currentNode->getOID() == NEXTID_NODE)
            $currentNode->setValue('value', ++$nextId, DATATYPE_ATTRIBUTE);

          if ($currentNode->getOID() == $parent->getOID())
          {
            // make a copy of the node, so that the original node without children
            // children will be saved in their own call to save()
            $insertNode = $node;
            foreach($insertNode->getChildren() as $child)
              $insertNode->deleteChild($child->getOID(), true);
            // determine add type of new child
            // NODE: we only distinguish between ADDCHILD_FRONT and default
            $children = $parent->getChildren();
            if ($children[0]->getOID() == $node->getOID())
              $currentNode->addChild($insertNode, ADDCHILD_FRONT);
            else
              $currentNode->addChild($insertNode);
            break;
          }
          $saveIter->proceed();
        }
      }
      else
        return false;
    }
    else if ($node->getState() == STATE_DIRTY)
    {
      // save existing node
      // precondition: the node exists in the database
      // search for parent
      while(!$saveIter->isEnd())
      {
        $currentNode = &$saveIter->getCurrentObject();
        if ($currentNode->getOID() == $node->getOID())
        {
          // save node by copying values
          foreach ($node->getDataTypes() as $type)
            foreach ($node->getValueNames($type) as $valueName)
            {
              $currentNode->setValue($valueName, $node->getValue($valueName, $type), $type);
              $currentNode->setValueProperties($valueName, $node->getValueProperties($valueName, $type), $type);
            }
          break;
        }
        $saveIter->proceed();
      }
    }
    unset($saveIter);
    $node->setState(STATE_CLEAN, false);
    // postcondition: the node is saved to the db
    //                the node id is updated
    //                the node state is STATE_CLEAN
    //                attributes are only inserted if their values differ from ''
    return true;
  }
  /**
   * Delete a Node from the xml file (together with all of its children).
   * @param oid The database id of the Node to delete
   * @param recursive True/False whether to physically delete it's children too [default: true]
   * @return True/False depending on success of operation
   * @attention recursive parameter is ignored here
   */
  function delete($oid, $recursive=true)
  {
    // NODE: changes will be done to the savetree, which is built by a call to startTransaction()
    // these changes will be made permanent by a call to commitTransaction().

    // precondition: node exists in savetree
    if ($this->_saveTree == null)
      return false;
      
    $saveIter = new NodeIterator($this->_saveTree);
    // search for parent
    while(!$saveIter->isEnd())
    {
      $currentNode = &$saveIter->getCurrentObject();
      if ($currentNode->getOID() == $oid)
      {
        // delete the child
        $parent = &$currentNode->getParent();
        $parent->deleteChild($oid, true);
        break;
      }
      $saveIter->proceed();
    }
    unset($saveIter);
    // postcondition: the node and all of its children are deleted from savetree
    return true;
  }
  /**
   * @see PersistenceMapper::startTransaction()
   * From now on all calls to save() and delete() will be executed to a temporary tree
   * that will be saved by the call to commitTransaction().
   */
  function startTransaction()
  {
    // build save tree
    $saveMapper = new NodeXMLMapper(array('filename' => $this->_filename));
    $this->_saveTree = &$saveMapper->load(1, BUILDDEPTH_INFINITE);
    $this->_intransaction = true;
    unset($saveMapper);
  }
  /**
   * @see PersistenceMapper::commitTransaction()
   * Save the temporary tree.
   */
  function commitTransaction()
  {
    // escape all values in save tree (they were unescaped while loading)
    $saveIter = new NodeIterator($this->_saveTree);
    while(!$saveIter->isEnd())
    {
      $currentNode = &$saveIter->getCurrentObject();
      foreach ($currentNode->getDataTypes() as $type)
        foreach ($currentNode->getValueNames($type) as $valueName)
          $currentNode->setValue($valueName, htmlspecialchars($currentNode->getValue($valueName, $type), ENT_QUOTES), $type);
      $saveIter->proceed();
    }
    // save modified xml tree
    $xmlos = new XMLOutputStrategy(strtolower($this->_filename), $this->_doctype, $this->_dtd);
    $saveIter->reset($this->_saveTree);
    $ov = new OutputVisitor($xmlos);
    $ov->startIterator($saveIter);  
    $this->_intransaction = false;
    // clean up
    unset($saveIter);
    unset($ov);
    unset($xmlos);
  }
  /**
   * @see PersistenceMapper::rollbackTransaction()
   * Nothing to do since the changes have to be explicitely committed.
   */
  function rollbackTransaction()
  {
  }
  /**
   * Get the next insert id.
   * @return next insert id
   */
  function getNextInsertId()
  {
    if (!$this->_intransaction)
    {
      // we are not in a transaction -> get id from file
      $insertMapper = new NodeXMLMapper(array('filename' => $this->_filename));
      $node = &$insertMapper->load(NEXTID_NODE, BUILDDEPTH_SINGLE);
      unset($insertMapper);
      return $node->getValue('value', DATATYPE_ATTRIBUTE);
    }
    else
    {
      // we are in a transaction -> get id from savetree
      $iter = new NodeIterator($this->_saveTree);
      while(!$iter->isEnd())
      {
        $currentNode = &$iter->getCurrentObject();
        if ($currentNode->getOID() == NEXTID_NODE)
        {
          unset($iter);
          return $currentNode->getValue('value', DATATYPE_ATTRIBUTE);
        }
        $iter->proceed();
      }
    }
  }
  
  /**
   * XML parser functions.
   */
   
  /**
   * Start Element Handler.
   * @param parser A reference to the XML parser calling the handler
   * @param name The name of the element for which this handler is called
   * @param attribs An associative array with the element's attributes 
   */  
  function parseStartTag($parser, $name, $attribs) 
  {
    $this->_curDepth++;
    if ($attribs['id'] == $this->_startId || $this->_loading && $this->isDepthValid())
    {
      // construct node
      $node = &NodeXMLMapper::create(strtolower($name), BUILDDEPTH_SINGLE);
      $node->setOID($attribs['id']);
      $node->setMapper($this);
      $this->_curNode = &$node;

      // set node's attributes
      foreach($attribs as $key => $value)
        if (strtolower($key) != 'id')
          $node->setValue(strtolower($key), stripslashes(stripslashes($value)), DATATYPE_ATTRIBUTE);

      if ($attribs['id'] == $this->_startId)
      {
        // start node -> init
        $this->_loading = true;
        $this->_startDepth = $this->_curDepth;
        if ($this->_prevId != -1)
          $node->setProperty('parentdbid', $this->_prevId);
        $this->_root = &$node;
      }
      else if ($this->_loading)
      {
        // child node -> add to parent
        $node->setProperty('parentdbid', $this->_curParent->getOID());
        $this->_curParent->addChild($node);
      }

      // update current parent
      $this->_curParent = &$node;
    }
    
    $this->_prevId = $attribs['id'];
  }
  /**
   * End Element Handler.
   * @param parser A reference to the XML parser calling the handler
   * @param name The name of the element for which this handler is called
   */  
  function parseEndTag($parser, $name) 
  {
    $this->_curDepth--;

    if ($this->_loading && $this->isDepthValid())
    {
      // update current parent
      $this->_curParent = &$this->_curParent->getParent();
      // stop loading if we reach the end tag of root (which has no parent) 
      if (!$this->_curParent)
        $this->_loading = false;
    }
  }
  /**
   * Element Handler.
   * @param parser A reference to the XML parser calling the handler
   * @param data The character data as a string
   */  
  function parseElement($parser, $data) 
  {
    $data = stripslashes(stripslashes(trim($data)));
    if ($data != '' && $this->_loading && $this->isDepthValid())
    {
      $value = $this->_curNode->getValue($this->_curNode->getType(), DATATYPE_ELEMENT).$data;
      $this->_curNode->setValue($this->_curNode->getType(), $value, DATATYPE_ELEMENT);
    }
  }
  /**
   * Check if the current depth is valid for building.
   * @return True/False whether the depth is valid
   */  
  function isDepthValid() 
  {
    return (($this->_curDepth-$this->_startDepth) <= $this->_buildDepth) || ($this->_buildDepth == BUILDDEPTH_INFINITE);
  }
}
?>

