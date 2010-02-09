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
require_once(BASE."wcmf/lib/persistence/converter/class.DataConverter.php");
require_once(BASE."wcmf/lib/model/class.Node.php");  
require_once(BASE."wcmf/lib/util/class.InifileParser.php");
require_once(BASE."wcmf/3rdparty/adodb/adodb.inc.php");

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
define("BUILDDEPTH_SINGLE",   -2);   // build only given node
define("BUILDDEPTH_GROUPED",  -3);  // build tree from given root on respecting the root property defined in element relations
define("BUILDDEPTH_REQUIRED", -4); // build tree from given root on respecting the required property defined in element relations

define("BUILDTYPE_COMPLETE", -1);  // build a node with all properties etc.
define("BUILDTYPE_NOPROPS",  -2);   // build a node without properties for itself and its attributes (saves memory space)

/**
 * @class NodeToSingleTableMapper
 * @ingroup Mapper
 * @brief NodeToSingleTableMapper maps nodes of different types to the database. It uses a
 * relational database scheme that simulates a xml tree. That means all nodes share one
 * table.
 * @deprecated Use NodeRDBMapper or NodeUnifiedRDBMapper instead
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NodeToSingleTableMapper extends PersistenceMapper
{
  var $_conn = null;          // database connection
  var $_rootElementId = 0;    // stores element id of root for affiliation verification of child nodes
  var $_rootId = 0;           // stores id of root
  var $_groupMap = array();   // stores grouped property (true/false) for child root pairs (key: childElementId_rootElementId)
  var $_type = '*';           // mapper type
  var $_dbPrefix = '';        // table prefix in database
  
  /**
   * Constructor.
   * @param params Initialization data given in an assoziative array with the following keys: 
   *               dbType, dbHostName, dbUserName, dbPassword, dbName OR dbConnection
   *               if dbPrefix is given it will be appended to every table string, which is
   *               usefull if different cms operate on the same database
   */
  function NodeToSingleTableMapper($params)
  {
    if (array_key_exists('dbType', $params) && array_key_exists('dbHostName', $params) && array_key_exists('dbUserName', $params) && 
      array_key_exists('dbPassword', $params) && array_key_exists('dbName', $params))
    {
      // create new connection
      $this->_conn = &ADONewConnection($params['dbType']);
      $connected = $this->_conn->PConnect($params['dbHostName'],$params['dbUserName'],$params['dbPassword'],$params['dbName']);
      if (!$connected)
        WCMFException::throwEx($this->_conn->ErrorMsg(), __FILE__, __LINE__);

      $this->_conn->replaceQuote = "\'";
      $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
      define(ADODB_OUTP, "gError");

      // get database prefix if defined
      $this->_dbPrefix = $params['dbPrefix'];

      // log sql if requested
      $parser = &InifileParser::getInstance();
      if (($logSQL = $parser->getValue('logSQL', 'cms')) === false)
        $logSQL = 0;
      $this->_conn->LogSQL($logSQL);
    }
    elseif (array_key_exists('dbConnection', $params))
    {
      // use existing connection
      $this->_conn = $params['dbConnection'];
    }
    else
    	WCMFException::throwEx("Wrong parameters for constructor.", __FILE__, __LINE__);
    	
    $this->_dataConverter = new DataConverter();
  }
  /**
   * Construct a Node from the database.
   * @param oid The object id of the Node to load
   * @param buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build (except BUILDDEPTH_REQUIRED)
   * @param buildType One of the BUILDTYPE constants describing the size to build
   * @param depth Internal use
   * @return A reference to the Node, null if oid does not exist.
   */
  function &load($oid, $buildDepth, $buildType=BUILDTYPE_COMPLETE, $depth=0)
  {
    if ($buildDepth < 0 && !in_array($buildDepth, array(BUILDDEPTH_INFINITE, BUILDDEPTH_SINGLE, BUILDDEPTH_GROUPED)))
    	WCMFException::throwEx("Build depth not supported: $buildDepth", __FILE__, __LINE__);
        
    $persistenceFacade = &PersistenceFacade::getInstance();
    $nodeDef = $persistenceFacade->decomposeOID($oid);

    // select node data
    $sqlStr = "SELECT ".$this->_dbPrefix."nodes.*, ".$this->_dbPrefix."elements.id as eid, ".$this->_dbPrefix."elements.element_name, ".$this->_dbPrefix."elements.visible,
                  ".$this->_dbPrefix."elements.editable, ".$this->_dbPrefix."elements.alt, ".$this->_dbPrefix."elements.hint, ".$this->_dbPrefix."elements.display_value,
                  ".$this->_dbPrefix."elements.input_type, ".$this->_dbPrefix."elements.restrictions, ".$this->_dbPrefix."elements.defaultval, ".$this->_dbPrefix."data_types.data_type 
               FROM ".$this->_dbPrefix."nodes, ".$this->_dbPrefix."elements LEFT JOIN ".$this->_dbPrefix."data_types
                  ON ".$this->_dbPrefix."elements.fk_data_types_id=".$this->_dbPrefix."data_types.id 
               WHERE ".$this->_dbPrefix."nodes.id=".$nodeDef['id']." AND ".$this->_dbPrefix."nodes.fk_n_elements_id=".$this->_dbPrefix."elements.id;";
               // left join on data_type, because it may be null (node without content)
    $rs = &$this->_conn->Execute($sqlStr);
    if (!$rs) 
    	WCMFException::throwEx($this->_conn->ErrorMsg(), __FILE__, __LINE__);
    else
      $nodeData = $rs->FetchRow();
    if (!$nodeData)
      return null;
    
    // store element id of root node
    // every grouped child has this element id as grouproot in element relations
    if ($depth == 0)
    {
      $this->_rootElementId = $nodeData['fk_n_elements_id'];
      $this->_rootId = $nodeDef['id'];
    }

    // construct node
    $node = new Node($nodeData['element_name'], $oid);
    $node->setMapper($this);
    if ($buildType != BUILDTYPE_NOPROPS)
    {
      // set node properties
      $node->setProperty('visible', $nodeData['visible']);
      $node->setProperty('editable', $nodeData['editable']);
      $node->setProperty('alt', $nodeData['alt']);
      $node->setProperty('hint', $nodeData['hint']);
      $node->setProperty('display_value', $nodeData['display_value']);
      $node->setProperty('input_type', $nodeData['input_type']);
      $node->setProperty('elementid', $nodeData['eid']);
      if ($nodeData['fk_nodes_id'] != '')
        $node->setProperty('parentoid', $persistenceFacade->composeOID(array('type' => $this->_type, 'id' => $nodeData['fk_nodes_id'])));
      $node->setProperty('rootoid', null);  // default value is null, if we have a grouproot it will be set afterwards
    }

    // get node data if exists
    if (isset($nodeData['data_type']))
    {
      // convert data
      $nodeValue = $this->_dataConverter->convertStorageToApplication($nodeData[$nodeData['data_type']], $nodeData['data_type'], $nodeData['element_name']);
      if ($nodeValue == '')
        $nodeValue = $nodeData['defaultval'];
      // set data
      $node->setValue($nodeData['element_name'], $nodeValue, DATATYPE_ELEMENT);
      if ($buildType != BUILDTYPE_NOPROPS)
      {
        $elementProperties = array('visible' => $nodeData['visible'],
                                   'restrictions' => stripslashes($nodeData['restrictions']),
                                   'defaultval' => $nodeData['defaultval'],
                                   'editable' => $nodeData['editable'],
                                   'alt' =>  $nodeData['alt'],
                                   'hint' =>  $nodeData['hint'],
                                   'input_type' =>  $nodeData['input_type'],
                                   'data_type' =>  $nodeData['data_type'],
                                   'id'=> $nodeData['eid']);
        $node->setValueProperties($nodeData['element_name'], $elementProperties, DATATYPE_ELEMENT);
      }
    }

    // get attributes of this node
    $sqlStr = "SELECT ".$this->_dbPrefix."attribs.*, ".$this->_dbPrefix."attrib_def.id as aid, ".$this->_dbPrefix."attrib_def.attrib_name, ".$this->_dbPrefix."attrib_def.optional,
                  ".$this->_dbPrefix."attrib_def.restrictions, ".$this->_dbPrefix."attrib_def.defaultval, ".$this->_dbPrefix."attrib_def.visible, ".$this->_dbPrefix."attrib_def.editable,
                  ".$this->_dbPrefix."attrib_def.alt, ".$this->_dbPrefix."attrib_def.hint, ".$this->_dbPrefix."attrib_def.input_type, ".$this->_dbPrefix."data_types.data_type 
               FROM ".$this->_dbPrefix."attribs, ".$this->_dbPrefix."attrib_def, ".$this->_dbPrefix."data_types
               WHERE ".$this->_dbPrefix."attribs.fk_nodes_id=".$nodeDef['id']." AND ".$this->_dbPrefix."attribs.fk_attrib_def_id=".$this->_dbPrefix."attrib_def.id
                  AND ".$this->_dbPrefix."attrib_def.fk_data_types_id=".$this->_dbPrefix."data_types.id ORDER BY ".$this->_dbPrefix."attrib_def.sort_key;";
    $rs = &$this->_conn->Execute($sqlStr);
    while ($rs && $nodeAttributes = $rs->FetchRow())
    {
      // convert data
      $attributeValue = $this->_dataConverter->convertStorageToApplication($nodeAttributes[$nodeAttributes['data_type']], $nodeAttributes['data_type'], $nodeAttributes['attrib_name']);
      if ($attributeValue == '')
        $attributeValue = $nodeAttributes['defaultval'];
      // set data
      $node->setValue($nodeAttributes['attrib_name'], $attributeValue, DATATYPE_ATTRIBUTE);
      if ($buildType != BUILDTYPE_NOPROPS)
      {
        $attributeProperties = array('visible' => $nodeAttributes['visible'],
                                     'restrictions' => $nodeAttributes['restrictions'],
                                     'defaultval' => $nodeAttributes['defaultval'],
                                     'editable' => $nodeAttributes['editable'],
                                     'alt' => $nodeAttributes['alt'],
                                     'optional' =>  $nodeAttributes['optional'],
                                     'hint' =>  $nodeAttributes['hint'],
                                     'input_type' =>  $nodeAttributes['input_type'],
                                     'data_type' =>  $nodeAttributes['data_type'],
                                     'id' =>  $nodeAttributes['aid']);
        $node->setValueProperties($nodeAttributes['attrib_name'], $attributeProperties, DATATYPE_ATTRIBUTE);
      }
    }
    
    // set sortkey
    $node->setValue('sort_key', $nodeData['sort_key'], DATATYPE_IGNORE);

    // get children of this node
    $childoids = array();
    $sqlStr = "SELECT ".$this->_dbPrefix."nodes.id, ".$this->_dbPrefix."nodes.fk_n_elements_id FROM ".$this->_dbPrefix."nodes
                  WHERE ".$this->_dbPrefix."nodes.fk_nodes_id=".$nodeDef['id']." ORDER BY sort_key;";
    $rs = &$this->_conn->Execute($sqlStr);
    while ($rs && $childData = $rs->FetchRow())
    {
      $childoid = $persistenceFacade->composeOID(array('type' => $this->_type, 'id' => $childData['id']));
      array_push($childoids, $childoid);
      if ( ($buildDepth != BUILDDEPTH_SINGLE) && 
           ( ($depth < $buildDepth) || 
             ($buildDepth == BUILDDEPTH_INFINITE) ||
             ( ($buildDepth == BUILDDEPTH_GROUPED) && $this->isGroupChild($childData['fk_n_elements_id'], $nodeData['fk_n_elements_id'], $this->_rootElementId) )
           ) 
         )
      {
        $childNode = &$persistenceFacade->load($childoid, $buildDepth, $buildType, $depth+1);
        $childNode->setMapper($this);
        if ($this->isGroupChild($childData['fk_n_elements_id'], $nodeData['fk_n_elements_id'], $this->_rootElementId))
          if ($buildType != BUILDTYPE_NOPROPS)
          {
            if ($this->_rootId != '')
            {
              $rootoid = $persistenceFacade->composeOID(array('type' => $this->_type, 'id' => $this->_rootId));
              $childNode->setProperty('rootoid', $rootoid);
            }
          }
        $node->addChild($childNode);
      }
    }
    if ($buildType != BUILDTYPE_NOPROPS)
      $node->setProperty('childoids', $childoids);
    $node->setState(STATE_CLEAN, false);
    return $node;
  }
  /**
   * Construct the template of a Node of a given type (defined by element name).
   * @param type The type of the Node to construct (maybe '' for an unspecified Node)
   * @param buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   * @param buildType One of the BUILDTYPE constants describing the size to build
   * @param depth Internal use
   * @return A reference to the Node.
   */
  function &create($type, $buildDepth, $buildType=BUILDTYPE_COMPLETE, $depth=0)
  {
    if ($buildDepth < 0 && !in_array($buildDepth, array(BUILDDEPTH_INFINITE, BUILDDEPTH_SINGLE, BUILDDEPTH_GROUPED, BUILDDEPTH_REQUIRED)))
    	WCMFException::throwEx("Build depth not supported: $buildDepth", __FILE__, __LINE__);

    $persistenceFacade = &PersistenceFacade::getInstance();
    // map _any_ node to empty node
    if ($type == '*') $type = '';

    $nodeData = array();
    if ($type != '')
    {
      $sqlStr = "SELECT ".$this->_dbPrefix."elements.id, ".$this->_dbPrefix."elements.visible, ".$this->_dbPrefix."elements.editable, ".$this->_dbPrefix."elements.alt,
                    ".$this->_dbPrefix."elements.hint, ".$this->_dbPrefix."elements.display_value, ".$this->_dbPrefix."elements.input_type, ".$this->_dbPrefix."elements.restrictions,
                    ".$this->_dbPrefix."elements.defaultval, ".$this->_dbPrefix."data_types.data_type
                 FROM ".$this->_dbPrefix."elements LEFT JOIN ".$this->_dbPrefix."data_types ON ".$this->_dbPrefix."elements.fk_data_types_id=".$this->_dbPrefix."data_types.id 
                 WHERE ".$this->_dbPrefix."elements.element_name='".$type."';"; // left join on data_type, because it may be null (node without content)
      $rs = &$this->_conn->Execute($sqlStr);
      if (!$rs || $rs->RecordCount() == 0)
      	WCMFException::throwEx("Can't construct template node ".$type.": unknown element", __FILE__, __LINE__);
      else
        $nodeData = $rs->FetchRow();
    }

    // store element id of root node
    // every grouped child has this element id as grouproot in element relations
    if ($depth == 0)
    {
      $this->_rootElementId = $nodeData['id'];
      $this->_rootId = $nodeData['id'];
    }
      
    // construct node
    $node = new Node($type);
    $node->setMapper($this);
    if ($buildType != BUILDTYPE_NOPROPS)
    {
      // set node properties
      $node->setProperty('visible', $nodeData['visible']);
      $node->setProperty('editable', $nodeData['editable']);
      $node->setProperty('alt', $nodeData['alt']);
      $node->setProperty('hint', $nodeData['hint']);
      $node->setProperty('display_value', $nodeData['display_value']);
      $node->setProperty('input_type', $nodeData['input_type']);
      $node->setProperty('elementid', $nodeData['id']);
      // NODE: we do not set the 'parentoid' property because it might be ambiguous (e.g. 'name' element might be used in different contexts)
      if ($this->_rootId != '')
      {
        $rootoid = $persistenceFacade->composeOID(array('type' => $this->_type, 'id' => $this->_rootId));
        $node->setProperty('rootoid', $rootoid);
      }
    }

    // set node data element if exists
    if (isset($nodeData['data_type']))
    {
      // convert data
      $nodeValue = $this->_dataConverter->convertStorageToApplication($nodeData['defaultval'], $nodeData['data_type'], $nodeData['element_name']);
      // set data
      $node->setValue($type, $nodeValue, DATATYPE_ELEMENT);
      if ($buildType != BUILDTYPE_NOPROPS)
      {
        $elementProperties = array('visible' => $nodeData['visible'],
                                   'restrictions' => stripslashes($nodeData['restrictions']),
                                   'defaultval' => $nodeData['defaultval'],
                                   'editable' => $nodeData['editable'],
                                   'alt' =>  $nodeData['alt'],
                                   'hint' =>  $nodeData['hint'],
                                   'input_type' =>  $nodeData['input_type'],
                                   'data_type' =>  $nodeData['data_type'],
                                   'id'=> $nodeData['id']);
        $node->setValueProperties($type, $elementProperties, DATATYPE_ELEMENT);
      }
    }

    // set attributes of this node
    $sqlStr = "SELECT ".$this->_dbPrefix."attrib_def.id, ".$this->_dbPrefix."attrib_def.attrib_name, ".$this->_dbPrefix."attrib_def.optional, ".$this->_dbPrefix."attrib_def.restrictions,
                  ".$this->_dbPrefix."attrib_def.defaultval, ".$this->_dbPrefix."attrib_def.visible, ".$this->_dbPrefix."attrib_def.editable, ".$this->_dbPrefix."attrib_def.hint,
                  ".$this->_dbPrefix."attrib_def.alt, ".$this->_dbPrefix."attrib_def.input_type, ".$this->_dbPrefix."data_types.data_type 
               FROM ".$this->_dbPrefix."attrib_def, ".$this->_dbPrefix."data_types
               WHERE ".$this->_dbPrefix."attrib_def.fk_elements_id=".$nodeData['id']." AND ".$this->_dbPrefix."attrib_def.fk_data_types_id=".$this->_dbPrefix."data_types.id
                  ORDER BY ".$this->_dbPrefix."attrib_def.sort_key;";
    $rs = &$this->_conn->Execute($sqlStr);
    while ($rs && $nodeAttributes = $rs->FetchRow())
    {
      // convert data
      $attributeValue = $this->_dataConverter->convertStorageToApplication($nodeAttributes['defaultval'], $nodeAttributes['data_type'], $nodeAttributes['attrib_name']);
      // set data
      $node->setValue($nodeAttributes['attrib_name'], $attributeValue, DATATYPE_ATTRIBUTE);
      if ($buildType != BUILDTYPE_NOPROPS)
      {
        $attributeProperties = array('visible' => $nodeAttributes['visible'],
                                     'restrictions' => $nodeAttributes['restrictions'],
                                     'defaultval' => $nodeAttributes['defaultval'],
                                     'editable' => $nodeAttributes['editable'],
                                     'alt' => $nodeAttributes['alt'],
                                     'optional' =>  $nodeAttributes['optional'],
                                     'hint' =>  $nodeAttributes['hint'],
                                     'input_type' =>  $nodeAttributes['input_type'],
                                     'data_type' =>  $nodeAttributes['data_type'],
                                     'id' =>  $nodeAttributes['id']);
        $node->setValueProperties($nodeAttributes['attrib_name'], $attributeProperties, DATATYPE_ATTRIBUTE);
      }
    }
    
    // set sortkey
    $node->setValue('sort_key', 0, DATATYPE_IGNORE);
    
    // set children of this node
    $childoids = array();
    $sqlStr = "SELECT ".$this->_dbPrefix."elements.element_name, ".$this->_dbPrefix."element_relations.fk_elements_child_id, ".$this->_dbPrefix."element_relations.repetitive,
                  ".$this->_dbPrefix."element_relations.optional
               FROM ".$this->_dbPrefix."element_relations, ".$this->_dbPrefix."elements
               WHERE ".$this->_dbPrefix."element_relations.fk_elements_id=".$nodeData['id']." AND ".$this->_dbPrefix."element_relations.fk_elements_child_id=".$this->_dbPrefix."elements.id
                  ORDER BY ".$this->_dbPrefix."element_relations.sort_key;";
    $rs = &$this->_conn->Execute($sqlStr);
    while ($rs && $childData = $rs->FetchRow())
    {
      $childoid = $persistenceFacade->composeOID(array('type' => $childData['element_name'], 'id' => $childData['fk_elements_child_id']));
      array_push($childoids, $childoid);

      // map 'repetitive', 'optional' to 'minOccurs', 'maxOccurs'
      $childData['minOccurs'] = 1; // default value
      $childData['maxOccurs'] = 1; // default value
      if ($childData['repetitive'] == 1)
        $childData['maxOccurs'] = 'unbounded';
      if ($childData['optional'] == 1)
        $childData['minOccurs'] = 0;

      if ( ($buildDepth != BUILDDEPTH_SINGLE) && 
           ( ($depth < $buildDepth) || 
             ($buildDepth == BUILDDEPTH_INFINITE) ||
             ( ($buildDepth == BUILDDEPTH_GROUPED) && $this->isGroupChild($childData['fk_elements_child_id'], $nodeData['id'], $this->_rootElementId) ) ||
             ( ($buildDepth == BUILDDEPTH_REQUIRED) && $childData['minOccurs'] > 0 )
           ) 
         )
      {
        $childNode = &$persistenceFacade->create($childData['element_name'], $buildDepth, $buildType, $depth+1);
        $childNode->setMapper($this);
        if ($buildType != BUILDTYPE_NOPROPS)
        {
          $childNode->setProperty('minOccurs', $childData['minOccurs']);
          $childNode->setProperty('maxOccurs', $childData['maxOccurs']);
        }
        $node->addChild($childNode);
      }
    }
    if ($buildType != BUILDTYPE_NOPROPS)
      $node->setProperty('childoids', $childoids);
    return $node;
  }
  /**
   * Save a Node to the database (inserted if it is new).
   * @param node A reference to the Node to save
   * @return True/False depending on success of operation
   */
  function save(&$node)
  {
    // prepare node data
    // escape all values
    foreach ($node->getDataTypes() as $type)
      foreach ($node->getValueNames($type) as $valueName)
      {
        $properties = $node->getValueProperties($valueName, $type);
        // NOTE: strip slashes from "'" and """ first because on INSERT/UPDATE we use ADODB's qstr
        // with second parameter false which will add slashes again
        // (we do this manually because we can't rely on get_magic_quotes_gpc())
        $value = str_replace(array("\'","\\\""), array("'", "\""), $node->getValue($valueName, $type));
        $node->setValue($valueName, $this->_dataConverter->convertApplicationToStorage($value, $properties['data_type'], $valueName), $type);
      }

    $persistenceFacade = &PersistenceFacade::getInstance();
    $nodeDef = $persistenceFacade->decomposeOID($node->getOID());
    if ($node->getState() == STATE_NEW)
    {
      // insert new node
      // precondition: the node has a parent and its id exists in the database
      $parent = &$node->getParent();
      $parentDef = $persistenceFacade->decomposeOID($parent->getOID());
      if ($parent != null)
      {
        // save node
        $sortkey = $node->getValue('sort_key', DATATYPE_IGNORE);
        if (!$sortkey) $sortkey = 0;
        // save node element if exist
        if (in_array(DATATYPE_ELEMENT, $node->getDataTypes()))
        {
          $elementValue = $node->getValue($node->getType(), DATATYPE_ELEMENT);
          $properties = $node->getValueProperties($node->getType(), DATATYPE_ELEMENT);
          if ($properties['data_type'] != '')
            $sqlStr = "INSERT INTO ".$this->_dbPrefix."nodes (fk_nodes_id, fk_n_elements_id, ".$properties['data_type'].", sort_key) 
                       VALUES (".$parentDef['id'].", ".$node->getProperty('elementid').", ".$this->_conn->qstr($elementValue).", ".$sortkey.");";
        }
        else
          $sqlStr = "INSERT INTO ".$this->_dbPrefix."nodes (fk_nodes_id, fk_n_elements_id, sort_key) 
                     VALUES (".$parentDef['id'].", ".$node->getProperty('elementid').", ".$sortkey.");";
        if ($this->_conn->Execute($sqlStr) === false)
        	WCMFException::throwEx("Error inserting node ".$node->getType().": ".$this->_conn->ErrorMsg(), __FILE__, __LINE__);
        	
        // update node oid        
        $node->setOID($persistenceFacade->composeOID(array('type' => $nodeDef['type'], 'id' => $this->_conn->Insert_ID())));
        $nodeDef = $persistenceFacade->decomposeOID($node->getOID());

        // save attributes
        $attributeNames = $node->getValueNames(DATATYPE_ATTRIBUTE);
        foreach($attributeNames as $attributeName)
        {
          // prepare attribute data
          $attributeValue = $node->getValue($attributeName, DATATYPE_ATTRIBUTE);
          $attributeProperties = $node->getValueProperties($attributeName, DATATYPE_ATTRIBUTE);
          if ($attributeProperties == null)
           	WCMFException::throwEx("Can't save ".$node->getType().".".$attributeName.": No properties found.", __FILE__, __LINE__);

          if ($attributeValue != '')
          {
            // save attribute data, if not empty
            $sqlStr = "INSERT INTO ".$this->_dbPrefix."attribs (fk_nodes_id, fk_attrib_def_id, ".$attributeProperties['data_type'].") 
                       VALUES (".$nodeDef['id'].", ".$attributeProperties['id'].", ".$this->_conn->qstr($attributeValue).");";
            if ($this->_conn->Execute($sqlStr) === false)
            	WCMFException::throwEx("Error inserting node ".$node->getType().": ".$this->_conn->ErrorMsg(), __FILE__, __LINE__);
          }
        }

        // log action
        $this->logAction(&$node);
      }
      else
        return false;
    }
    else if ($node->getState() == STATE_DIRTY)
    {
      // save existing node
      // precondition: the node exists in the database

      // log action
      $this->logAction(&$node);

      // save node
      $sortkey = $node->getValue('sort_key', DATATYPE_IGNORE);
      if (!$sortkey) $sortkey = 0;
      // save node element if exist
      if (in_array(DATATYPE_ELEMENT, $node->getDataTypes()))
      {
        $elementValue = $node->getValue($node->getType(), DATATYPE_ELEMENT);
        $properties = $node->getValueProperties($node->getType(), DATATYPE_ELEMENT);
        if ($properties['data_type'] != '')
          $sqlStr = "UPDATE ".$this->_dbPrefix."nodes SET ".$properties['data_type']."=".$this->_conn->qstr($elementValue).", sort_key=".$sortkey." WHERE id=".$nodeDef['id'].";";
      }
      else
        $sqlStr = "UPDATE ".$this->_dbPrefix."nodes SET sort_key=".$sortkey." WHERE id=".$nodeDef['id'].";";
      if ($this->_conn->Execute($sqlStr) === false)
      	WCMFException::throwEx("Error updating node ".$node->getType().": ".$this->_conn->ErrorMsg(), __FILE__, __LINE__);
      	
      // save attributes
      $attributeNames = $node->getValueNames(DATATYPE_ATTRIBUTE);
      foreach($attributeNames as $attributeName)
      {
        // prepare attribute data
        $attributeValue = $node->getValue($attributeName, DATATYPE_ATTRIBUTE);
        $attributeProperties = $node->getValueProperties($attributeName, DATATYPE_ATTRIBUTE);
        if ($attributeProperties == null)
         	WCMFException::throwEx("Can't save ".$node->getType().".".$attributeName.": No properties found.", __FILE__, __LINE__);

        // see if attribute exists
        $sqlStr = "SELECT ".$this->_dbPrefix."attribs.id FROM ".$this->_dbPrefix."attribs, ".$this->_dbPrefix."nodes, ".$this->_dbPrefix."attrib_def
                   WHERE ".$this->_dbPrefix."attribs.fk_nodes_id=".$nodeDef['id']." AND ".$this->_dbPrefix."attribs.fk_attrib_def_id=".$this->_dbPrefix."attrib_def.id
                      AND ".$this->_dbPrefix."attrib_def.attrib_name='".$attributeName."';";
       	$attribId = null;
        if ($rs = &$this->_conn->Execute($sqlStr))
        {
          $attribute = $rs->FetchRow();
          $attribId = $attribute['id'];
        }

        if (!$attribId)
        {
          if ($attributeValue != '')
          {
            // insert attribute data, if not empty
            $sqlStr = "INSERT INTO ".$this->_dbPrefix."attribs (fk_nodes_id, fk_attrib_def_id, ".$attributeProperties['data_type'].") 
                       VALUES (".$nodeDef['id'].", ".$attributeProperties['id'].", ".$this->_conn->qstr($attributeValue).");";
            if ($this->_conn->Execute($sqlStr) === false)
            	WCMFException::throwEx("Error updating node ".$node->getType().": ".$this->_conn->ErrorMsg(), __FILE__, __LINE__);
          }
        }
        else
        {
          // save attribute data
          $sqlStr = "UPDATE ".$this->_dbPrefix."attribs SET ".$attributeProperties['data_type']."=".$this->_conn->qstr($attributeValue)."
                     WHERE id=".$attribId.";";
          if ($this->_conn->Execute($sqlStr) === false)
          	WCMFException::throwEx("Error updating node ".$node->getType().": ".$this->_conn->ErrorMsg(), __FILE__, __LINE__);
        }
      }
    }
    $node->setState(STATE_CLEAN, false);
    // postcondition: the node is saved to the db
    //                the node oid is updated
    //                the node state is STATE_CLEAN
    //                attributes are only inserted if their values differ from ''
    return true;
  }
  /**
   * Delete a Node from the database (together with all of its children).
   * @param oid The object id of the Node to delete
   * @param recursive True/False whether to physically delete it's children too [default: true]
   * @return True/False depending on success of operation
   */
  function delete($oid, $recursive=true)
  {
    $persistenceFacade = &PersistenceFacade::getInstance();
    $nodeDef = $persistenceFacade->decomposeOID($oid);

    // log action
    if ($this->isLogging())
    {
      $obj = &$persistenceFacade->load($oid, BUILDDEPTH_SINGLE);
      if ($obj) 
        $this->logAction(&$obj);
    }
    // delete node    
    $sqlStr = "DELETE FROM ".$this->_dbPrefix."nodes WHERE id=".$nodeDef['id'].";";
    if ($this->_conn->Execute($sqlStr) === false)
    	WCMFException::throwEx("Error deleting node ".$oid.": ".$this->_conn->ErrorMsg(), __FILE__, __LINE__);
    $sqlStr = "DELETE FROM ".$this->_dbPrefix."attribs WHERE fk_nodes_id=".$nodeDef['id'].";";
    if ($this->_conn->Execute($sqlStr) === false)
    	WCMFException::throwEx("Error deleting node ".$oid.": ".$this->_conn->ErrorMsg(), __FILE__, __LINE__);

    // delete children
    if ($recursive)
    {
      $sqlStr = "SELECT ".$this->_dbPrefix."nodes.id, element_name FROM ".$this->_dbPrefix."nodes, ".$this->_dbPrefix."elements
                  WHERE ".$this->_dbPrefix."nodes.fk_n_elements_id=".$this->_dbPrefix."elements.id AND ".$this->_dbPrefix."nodes.fk_nodes_id=".$nodeDef['id'].";";
      $rs = &$this->_conn->Execute($sqlStr);
      while ($rs && $childData = $rs->FetchRow())
      {
        $childoid = $persistenceFacade->composeOID(array('type' => $childData['element_name'], 'id' => $childData['id']));
        $persistenceFacade->delete($childoid, $recursive);
      }
    }
    // postcondition: the node and all of its children are deleted from db
    return true;
  }
  /**
   * Get the database connection.
   * @return A reference to the ADONewConnection object
   */
  function &getConnection()
  {
    return $this->_conn;
  }
  /**
   * See if a child Node is grouped with a root Node.
   * @param childElementId The element id of the child Node
   * @param parentElementId The element id of the parent Node (necessary to identify relation)
   * @param rootElementId The element id of the root Node
   * @return true/false
   */
  function isGroupChild($childElementId, $parentElementId, $rootElementId)
  {
    $key = $childElementId.'_'.$parentElementId.'_'.$rootElementId;
    if (isset($this->_groupMap[$key]))
      return $this->_groupMap[$key];
    else
    {
      $groupped = false;
      $sqlStr = "SELECT fk_elements_id, grouproot FROM ".$this->_dbPrefix."element_relations WHERE fk_elements_child_id=".$childElementId.";";
      $rs = &$this->_conn->Execute($sqlStr);
      while ($rs && $relation = $rs->FetchRow())
        if ($relation['grouproot'] == $rootElementId && $relation['fk_elements_id'] == $parentElementId)
          $grouped = true;
      $this->_groupMap[$key] = $grouped;
      return $grouped;
    }
  }
  /**
   * Get the object ids of nodes matching a given criteria.
   * @param type The type of the object
   * @param criteria An assoziative array holding name value pairs of attributes (maybe null). Values maybe substrings of values to search for. [default: null]
   * @return An array containing the node oids
   */
  function getOIDs($type, $criteria=null)
  {
    $persistenceFacade = &PersistenceFacade::getInstance();
    $oids = array();

    // use criteria condition if criteria is given
    $attribTableStr = '';
    $attribCondStr = '';
    if ($criteria != null)
    {
      $i = 1;
      foreach($criteria as $name => $value)
      {
        $attribTableStr .= ", ".$this->_dbPrefix."attribs as attribs".$i.", ".$this->_dbPrefix."attrib_def as attrib_def".$i." ";
        $attribCondStr .= "AND attribs".$i.".fk_nodes_id=".$this->_dbPrefix."nodes.id AND attribs".$i.".fk_attrib_def_id=attrib_def".$i.".id AND attrib_def".$i.".attrib_name='".$name."' AND 
                          (attribs".$i.".data_txt LIKE '%".$value."%' OR attribs".$i.".data_blob LIKE '%".$value."%' OR attribs".$i.".data_date LIKE '%".$value."%' OR attribs".$i.".data_float LIKE '%".$value."%' OR attribs".$i.".data_int LIKE '%".$value."%' OR attribs".$i.".data_boolean LIKE '%".$value."%') ";
        $i++;
      }
    }

    $sqlStr = "SELECT ".$this->_dbPrefix."nodes.id FROM ".$this->_dbPrefix."nodes, ".$this->_dbPrefix."elements ".$attribTableStr."
                WHERE ".$this->_dbPrefix."nodes.fk_n_elements_id=".$this->_dbPrefix."elements.id AND ".$this->_dbPrefix."elements.element_name='".$type."' ".$attribCondStr;      
    $rs = &$this->_conn->Execute($sqlStr);
    if (!$rs) 
    	WCMFException::throwEx($this->_conn->ErrorMsg(), __FILE__, __LINE__);
    else
    {
      while ($row = $rs->FetchRow())
        array_push($oids, $persistenceFacade->composeOID(array('type' => $type, 'id' => $row['id'])));
    }
    return $oids;
  }
  /**
   * Get a property (element of the node or element of a child node) of all nodes of a given type (e.g. shortnames of all contractors).
   * If attribute is given only properties with the given value of this attribute are returned (e.g. shortnames which language attribute is 'en').
   * @note The properties database type MUST be 'data_txt'.
   * @param type The type of the node (e.g. 'contractor')
   * @param property The type of the child node (e.g. 'shortname'). If property is null the element of the type itself will be taken
   * @param attribute An assoziative array holding name value pairs of attributes (maybe null). Values maybe substrings of values to search for. [default: null]
   * @param oid If oid is given the property map will only be build for this single node [default: null]
   * @return An assoziative array containing the ids of the type nodes as keys and the element values of the property nodes as values
   */
  function getPropertyMap($type, $property, $attribute=null, $oid=null)
  {
    $map = array();
    $persistenceFacade = &PersistenceFacade::getInstance();

    // use id condition if id is given
    $idCondition = '';
    if ($oid != null)
    {
      $nodeDef = $persistenceFacade->decomposeOID($oid);
      $idCondition = $this->_dbPrefix.'nodes.id='.$nodeDef['id'].' AND';
    }
  
    // use attribute condition if attribute is given
    $attribTableStr = '';
    $attribCondStr = '';
    if ($attribute != null)
    {
      $i = 1;
      foreach($attribute as $name => $value)
      {
        $attribTableStr .= ", ".$this->_dbPrefix."attribs as attribs".$i.", ".$this->_dbPrefix."attrib_def as attrib_def".$i." ";
        $attribCondStr .= "AND attribs".$i.".fk_nodes_id=nodes2.id AND attribs".$i.".fk_attrib_def_id=attrib_def".$i.".id AND attrib_def".$i.".attrib_name='".$name."' AND 
                          (attribs".$i.".data_txt LIKE '%".$value."%' OR attribs".$i.".data_blob LIKE '%".$value."%' OR attribs".$i.".data_date LIKE '%".$value."%' OR attribs".$i.".data_float LIKE '%".$value."%' OR attribs".$i.".data_int LIKE '%".$value."%' OR attribs".$i.".data_boolean LIKE '%".$value."%') ";
        $i++;
      }
    }

    // get properties
    if ($property != null)
      $sqlStr = "SELECT ".$this->_dbPrefix."nodes.id, nodes2.data_txt 
                 FROM ".$this->_dbPrefix."nodes, ".$this->_dbPrefix."nodes as nodes2, ".$this->_dbPrefix."elements, ".$this->_dbPrefix."elements as elements2 ".$attribTableStr."
                 WHERE ".$idCondition." ".$this->_dbPrefix."nodes.id=nodes2.fk_nodes_id AND ".$this->_dbPrefix."nodes.fk_n_elements_id=".$this->_dbPrefix."elements.id
                    AND ".$this->_dbPrefix."elements.element_name='".$type."' AND nodes2.fk_n_elements_id=elements2.id AND elements2.element_name='".$property."' ".$attribCondStr.";";
    else
      $sqlStr = "SELECT ".$this->_dbPrefix."nodes.id, ".$this->_dbPrefix."nodes.data_txt FROM ".$this->_dbPrefix."nodes, ".$this->_dbPrefix."nodes as nodes2,
                    ".$this->_dbPrefix."elements ".$attribTableStr." 
                 WHERE ".$idCondition." ".$this->_dbPrefix."nodes.id=nodes2.id AND ".$this->_dbPrefix."nodes.fk_n_elements_id=".$this->_dbPrefix."elements.id
                    AND ".$this->_dbPrefix."elements.element_name='".$type."' ".$attribCondStr.";";
    $rs = &$this->_conn->Execute($sqlStr);
    if (!$rs) 
    	WCMFException::throwEx($this->_conn->ErrorMsg(), __FILE__, __LINE__);
    else
    {
      while ($row = $rs->FetchRow())
      {
        $oid = $persistenceFacade->composeOID(array('type' => $type, 'id' => $row['id']));
        $map[$oid] = $this->_dataConverter->convertStorageToApplication($row['data_txt'], 'data_txt', '');
      }
    }
    return $map;
  }
  /**
   * Get the object id of the (next) parent (of a given type) of a node.
   * @param oid The oid of the node to find the parent for
   * @param type The type of the parent to find (if null the next parent of any type is returned)
   * @param buildDepth One of the constants BUILDDEPTH_SINGLE (direct parent), BUILDDEPTH_GROUPED (group parent) [default: BUILDDEPTH_SINGLE]
   * @return The oid of the found parent Node / null if not found
   */
  function getParentOID($oid, $type=null, $buildDepth=BUILDDEPTH_SINGLE)
  {
    $parentOID = null;
    $persistenceFacade = &PersistenceFacade::getInstance();
    $nodeDef = $persistenceFacade->decomposeOID($oid);

    if ($buildDepth == BUILDDEPTH_GROUPED)
    {
      // grouped mode
      // get the element type of the grouproot
      $sqlStr = "SELECT ".$this->_dbPrefix."elements.element_name FROM ".$this->_dbPrefix."nodes, ".$this->_dbPrefix."nodes as parentnode, ".$this->_dbPrefix."elements,
                    ".$this->_dbPrefix."element_relations
                 WHERE ".$this->_dbPrefix."nodes.fk_n_elements_id=".$this->_dbPrefix."element_relations.fk_elements_child_id
                    AND parentnode.fk_n_elements_id=".$this->_dbPrefix."element_relations.fk_elements_id AND ".$this->_dbPrefix."nodes.fk_nodes_id=parentnode.id
                    AND ".$this->_dbPrefix."element_relations.grouproot=".$this->_dbPrefix."elements.id AND ".$this->_dbPrefix."nodes.id=".$nodeDef['id'];
      /*
      $sqlStr = "SELECT elements.element_name FROM nodes, elements, element_relations 
                 WHERE nodes.fk_n_elements_id=element_relations.fk_elements_child_id AND element_relations.grouproot=elements.id AND nodes.id=".$nodeDef['id'];
      */
      $rs = &$this->_conn->Execute($sqlStr);
      if (!$rs) 
      	WCMFException::throwEx($this->_conn->ErrorMsg(), __FILE__, __LINE__);
      else
      {
        $row = $rs->FetchRow();
        // get next parent with grouproots element name
        return $this->getParentOID($oid, $row['element_name'], BUILDDEPTH_SINGLE);
      }
    }
    else
    {
      // ungrouped mode
      $sqlStr = "SELECT ".$this->_dbPrefix."nodes.id, ".$this->_dbPrefix."nodes.fk_nodes_id, ".$this->_dbPrefix."elements.element_name
                  FROM ".$this->_dbPrefix."nodes, ".$this->_dbPrefix."elements WHERE ".$this->_dbPrefix."nodes.fk_n_elements_id=".$this->_dbPrefix."elements.id
                    AND ".$this->_dbPrefix."nodes.id=".$nodeDef['id'].";";
      $rs = &$this->_conn->Execute($sqlStr);
      if (!$rs) 
      	WCMFException::throwEx($this->_conn->ErrorMsg(), __FILE__, __LINE__);
      else
      {
        $row = $rs->FetchRow();
        $parentOID = $persistenceFacade->composeOID(array('type' => $row['element_name'], 'id' => $row['fk_nodes_id']));
        // the parent id is $row['fk_nodes_id']
        if ($type != null)
        {      
          // find parent of given type
          while ($row['element_name'] != $type && $row['fk_nodes_id'] != '')
          {
            $sqlStr = "SELECT ".$this->_dbPrefix."nodes.id, ".$this->_dbPrefix."nodes.fk_nodes_id, ".$this->_dbPrefix."elements.element_name
                        FROM ".$this->_dbPrefix."nodes, ".$this->_dbPrefix."elements WHERE ".$this->_dbPrefix."nodes.fk_n_elements_id=".$this->_dbPrefix."elements.id
                          AND ".$this->_dbPrefix."nodes.id=".$row['fk_nodes_id'].";";
            $rs = &$this->_conn->Execute($sqlStr);
            if (!$rs) 
            	WCMFException::throwEx($this->_conn->ErrorMsg(), __FILE__, __LINE__);
            else
              $row = $rs->FetchRow();
            if ($row['element_name'] != '' || $row['id'] != '')
              $parentOID = $persistenceFacade->composeOID(array('type' => $row['element_name'], 'id' => $row['id']));
            else
              $parentOID = null;
          }
        }
      }
    }
    return $parentOID;
  }
}
?>
