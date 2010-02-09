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
require_once(BASE."wcmf/lib/util/class.Message.php");
require_once(BASE."wcmf/lib/persistence/class.PersistenceMapper.php");
require_once(BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(BASE."wcmf/lib/persistence/converter/class.DataConverter.php");
require_once(BASE."wcmf/lib/model/class.Node.php");
require_once(BASE."wcmf/lib/util/class.XMLUtil.php");
require_once(BASE."wcmf/lib/util/class.Log.php");

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

/**
 * @class NodeXMLDBMapper
 * @ingroup Mapper
 * @brief NodeXMLDBMapper maps Node objects to a xml file using the CXmlDb class.
 * http://sourceforge.net/projects/phpxmldb
 *
 * @todo insert doctype, dtd into XML file
 * @todo when inserting children to a Node with text content the content is duplicated
 * @deprecated Use NodeXMLMapper
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NodeXMLDBMapper extends PersistenceMapper
{
  // xml file variables
  var $_filename = '';
  var $_doctype = '';
  var $_dtd = '';
  // the XMLUtil instance
  var $_db = null;
  // transaction status
  var $_inTransaction = false;

  /**
   * Constructor.
   * @param params Initialization data given in an assoziative array with the following keys:
   *               filename, doctype, dtd
   */
  function NodeXMLDBMapper($params)
  {
    if (array_key_exists('filename', $params) && array_key_exists('doctype', $params) && array_key_exists('dtd', $params))
    {
      $this->_filename = $params['filename'];
      $this->_doctype  = $params['doctype'];
      $this->_dtd      = $params['dtd'];

      // setup xml database
      $this->_db = new XMLUtil();
      $this->_db->SetOptions(array('TimeStampFlag' => 0, 'XmlOptions' => array(XML_OPTION_SKIP_WHITE => 1)));

      // Security settings
      $this->aDbPermissions = array(
      			'GetNodeData'  => XMLDB_PERMISSION_ENABLE,
      			'GetChildData' => XMLDB_PERMISSION_ENABLE,
      			'InsertNode'   => XMLDB_PERMISSION_ENABLE,
      			'UpdateNode'   => XMLDB_PERMISSION_ENABLE,
      			'RemoveNode'   => XMLDB_PERMISSION_ENABLE
      );
      // Pass down the security mode settings.
      $this->_db->bSecureMode = TRUE;
      foreach ($this->aDbPermissions as $MethodName => $Permission)
      	$this->_db->aPermissions[$MethodName] = $Permission;
    }
    else
    	WCMFException::throwEx("Wrong parameters for constructor.", __FILE__, __LINE__);

    $this->_dataConverter = new DataConverter();

    // call commitTransaction() on shutdown for automatic transaction end
    register_shutdown_function(array(&$this, 'commitTransaction'));
  }
  /**
   * Set the data file. Ends the transaction on the existing file.
   * @param params Initialization data given in an assoziative array with the following keys:
   *               filename, doctype, dtd
   */
  function setFilename($params)
  {
    if (array_key_exists('filename', $params) && array_key_exists('doctype', $params) && array_key_exists('dtd', $params))
    {
      if ($params['filename'] == $this->_filename)
        return;

      // commit transaction on exisiting file
      $this->commitTransaction();

      $this->_filename = $params['filename'];
      $this->_doctype  = $params['doctype'];
      $this->_dtd      = $params['dtd'];
    }
    else
    	WCMFException::throwEx("Wrong parameters.", __FILE__, __LINE__);
  }
  /**
   * @see PersistenceMapper::getPkNames()
   */
  function getPkNames()
  {
    return array('id' => DATATYPE_IGNORE);
  }
  /**
   * @see PersistenceMapper::loadImpl()
   */
  function &loadImpl($oid, $buildDepth, $buildAttribs=null, $buildTypes=null)
  {
    if ($buildDepth < 0 && !in_array($buildDepth, array(BUILDDEPTH_INFINITE, BUILDDEPTH_SINGLE)))
    	WCMFException::throwEx("Build depth not supported: $buildDepth", __FILE__, __LINE__);

    // auto start transaction
    $this->startReadTransaction();
  	//WCMFException::throwEx("No Connection. Start transaction first", __FILE__, __LINE__);

    $persistenceFacade = &PersistenceFacade::getInstance();
    $nodeDef = $persistenceFacade->decomposeOID($oid);

    // check buildTypes
    if (is_array($buildTypes) && !in_array($nodeDef['type'], $buildTypes))
      return null;

    // get node definition (default values, properties)
    $nodeData = $this->getNodeDefinition($nodeDef['type']);

    // find value with type DATATYPE_ELEMENT (only one is allowed)
    foreach ($nodeData['_datadef'] as $dataDef)
      if ($dataDef['app_data_type'] == DATATYPE_ELEMENT)
      {
        $elementName = $dataDef['name'];
        break;
      }

    // select node data
    if (strlen($elementName) > 0)
    {
      $nodeData['_data'] = $this->_db->GetNodeData($oid, $elementName);
      if ($nodeData['_data'] == null)
      	WCMFException::throwEx("Could not load Node with OID: ".$oid, __FILE__, __LINE__);
    }

    // construct node
    $node = &$this->createObject($nodeDef['type'], $oid);

    // set node properties
    foreach($nodeData['_properties'] as $property)
      $node->setProperty($property['name'], $property['value']);
    $i = 0;
    $parentoids = array();
    while (array_key_exists('pid'+$i, $nodeData['_data']))
    {
      $parentoid = $persistenceFacade->composeOID(array('type' => $nodeData['_data']['ptype'+$i], 'id' => $nodeData['_data']['pid'+$i]));
      array_push($parentoids, $parentoid);
      $i++;
    }
    $node->setProperty('parentoids', $parentoids);

    // get attributes to load
    $attribs = null;
    if ($buildAttribs != null && array_key_exists($nodeDef['type'], $buildAttribs))
      $attribs = $buildAttribs[$nodeDef['type']];

    // set node data
    foreach($nodeData['_datadef'] as $dataItem)
    {
      if ($attribs == null || in_array($dataItem['name'], $attribs))
      {
        $value = $this->_dataConverter->convertStorageToApplication($nodeData['_data'][$dataItem['name']], $dataItem['db_data_type'], $dataItem['name']);
        $node->setValue($dataItem['name'], $value, $dataItem['app_data_type']);
        $valueProperties = array();
        foreach($dataItem as $key => $value)
          if ($key != 'name' && $key != 'app_data_type')
            $valueProperties[$key] = $value;
        $node->setValueProperties($dataItem['name'], $valueProperties, $dataItem['app_data_type']);
      }
    }

    // recalculate build depth for the next generation
    if ($buildDepth != BUILDDEPTH_SINGLE && $buildDepth != BUILDDEPTH_INFINITE && $buildDepth > 0)
      $newBuildDepth = $buildDepth-1;
    else
      $newBuildDepth = $buildDepth;

    // get children of this node
    $childoids = array();
    $childrenData = $this->_db->GetChildData($oid);
    foreach($childrenData as $childData)
    {
      $childoid = $persistenceFacade->composeOID(array('type' => $childData['type'], 'id' => $childData['id']));
      array_push($childoids, $childoid);
      if ( ($buildDepth != BUILDDEPTH_SINGLE) && ($buildDepth > 0 || $buildDepth == BUILDDEPTH_INFINITE) )
      {
        $childNode = &$persistenceFacade->load($childoid, $newBuildDepth, $buildAttribs, $buildTypes);
        if ($childNode != null)
          $node->addChild($childNode);
      }
    }
    $node->setProperty('childoids', $childoids);
    $node->setState(STATE_CLEAN, false);
    return $node;
  }
  /**
   * @see PersistenceMapper::createImpl()
   */
  function &createImpl($type, $buildDepth, $buildAttribs)
  {
    if ($buildDepth < 0 && !in_array($buildDepth, array(BUILDDEPTH_INFINITE, BUILDDEPTH_SINGLE, BUILDDEPTH_REQUIRED)))
    	WCMFException::throwEx("Build depth not supported: $buildDepth", __FILE__, __LINE__);

    $persistenceFacade = &PersistenceFacade::getInstance();

    // get node definition (default values, properties)
    $nodeData = $this->getNodeDefinition($type);

    // construct node
    $node = &$this->createObject($type);

    // set node properties
    foreach($nodeData['_properties'] as $property)
      $node->setProperty($property['name'], $property['value']);
    // NOTE: we do not set the 'parentoids' property because it might be ambiguous (e.g. 'name' type might be used in different contexts)

    // get attributes to load
    $attribs = null;
    if ($buildAttribs != null && array_key_exists($type, $buildAttribs))
      $attribs = $buildAttribs[$type];

    // set node data
    foreach($nodeData['_datadef'] as $dataItem)
    {
      if ($attribs == null || in_array($dataItem['name'], $attribs))
      {
        $value = $this->_dataConverter->convertStorageToApplication($dataItem['default'], $dataItem['db_data_type'], $dataItem['name']);
        $node->setValue($dataItem['name'], $value, $dataItem['app_data_type']);
        $valueProperties = array();
        foreach($dataItem as $key => $value)
          if ($key != 'name' && $key != 'app_data_type')
            $valueProperties[$key] = $value;
        $node->setValueProperties($dataItem['name'], $valueProperties, $dataItem['app_data_type']);
      }
    }

    // recalculate build depth for the next generation
    if ($buildDepth != BUILDDEPTH_REQUIRED && $buildDepth != BUILDDEPTH_SINGLE && $buildDepth != BUILDDEPTH_INFINITE && $buildDepth > 0)
      $newBuildDepth = $buildDepth-1;
    else
      $newBuildDepth = $buildDepth;

    // prevent infinite recursion
    if ($buildDepth < BUILDDEPTH_MAX)
    {
      // set children of this node
      foreach ($nodeData['_children'] as $childData)
      {
        // set 'minOccurs', 'maxOccurs'
        if (!array_key_exists('minOccurs', $childData))
          $childData['minOccurs'] = 0; // default value
        if (!array_key_exists('maxOccurs', $childData))
          $childData['maxOccurs'] = 1; // default value

        if ( ($buildDepth != BUILDDEPTH_SINGLE) && (($buildDepth > 0) || ($buildDepth == BUILDDEPTH_INFINITE) ||
          (($buildDepth == BUILDDEPTH_REQUIRED) && $childData['minOccurs'] > 0 && $childData['aggregation'] == true)) )
        {
          $childNode = &$persistenceFacade->create($childData['type'], $newBuildDepth, $buildAttribs);
          $childNode->setProperty('minOccurs', $childData['minOccurs']);
          $childNode->setProperty('maxOccurs', $childData['maxOccurs']);
          $childNode->setProperty('aggregation', $childData['aggregation']);
          $childNode->setProperty('composition', $childData['composition']);
          $node->addChild($childNode);
        }
      }
    }
    return $node;
  }
  /**
   * @see PersistenceMapper::saveImpl()
   */
  function saveImpl(&$node)
  {
    if ($node->getType() == $this->_db->getRootNodeName())
      return false;

    // auto start transaction
    $this->startTransaction();
  	//WCMFException::throwEx("No Connection. Start transaction first", __FILE__, __LINE__);

    // prepare node data
    // escape all values
    $appValues = array();
    foreach ($node->getDataTypes() as $type)
      foreach ($node->getValueNames($type) as $valueName)
      {
        $properties = $node->getValueProperties($valueName, $type);
        $appValues[$type][$valueName] = $node->getValue($valueName, $type);
        // NOTE: strip slashes from "'" and """ first
        $value = str_replace(array("\'","\\\""), array("'", "\""), $appValues[$type][$valueName]);
        $node->setValue($valueName, $this->_dataConverter->convertApplicationToStorage($value, $properties['db_data_type'], $valueName), $type);
      }

    $persistenceFacade = &PersistenceFacade::getInstance();
    if ($node->getState() == STATE_NEW)
    {
      // insert new node as child of given parent
      $parentOID = null;
      $parent = $node->getParent();
      if ($parent != null)
        $parentOID = $parent->getOID();
      else if (sizeof($node->getProperty('parentoids')) > 0)
      {
        $poids = $node->getProperty('parentoids');
        $parentOID = $poids[0];
      }

      // add to root, if parentOID is invalid
      if (!PersistenceFacade::isValidOID($parentOID))
        $parentOID = null;

      $newID = $this->_db->InsertNode($node, $parentOID);
      if($newID == 0)
        WCMFException::throwEx("Error inserting node ".$node->getType().": ".$this->_db->getErrorMsg(), __FILE__, __LINE__);

      // log action
      $this->logAction(&$node);
    }
    else if ($node->getState() == STATE_DIRTY)
    {
      // save existing node
      // precondition: the node exists in the database

      // log action
      $this->logAction(&$node);

      // save node
      if(!$this->_db->UpdateNode($node))
        	WCMFException::throwEx("Error updating node ".$node->getType().": ".$this->_db->getErrorMsg(), __FILE__, __LINE__);
    }

    // set escaped values back to application values
    foreach ($node->getDataTypes() as $type)
      foreach ($node->getValueNames($type) as $valueName)
        $node->setValue($valueName, $appValues[$type][$valueName], $type);

    $node->setState(STATE_CLEAN, false);
    // postcondition: the node is saved to the db
    //                the node state is STATE_CLEAN
    //                attributes are only inserted if their values differ from ''

    return true;
  }
  /**
   * @see PersistenceMapper::deleteImpl()
   */
  function deleteImpl($oid, $recursive=true)
  {
    // auto start transaction
    $this->startTransaction();
  	//WCMFException::throwEx("No Connection. Start transaction first", __FILE__, __LINE__);

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
    if(!$this->_db->RemoveNode($oid))
    	WCMFException::throwEx("Error deleting node ".$oid.": ".$this->_db->getErrorMsg(), __FILE__, __LINE__);

    // delete children (in xml there is no difference between aggregation and composition, all children are deleted)
    if ($recursive)
    {
      $childrenData = $this->_db->GetChildData($oid);
      foreach($childrenData as $childData)
      {
        $childoid = $persistenceFacade->composeOID(array('type' => $childData['type'], 'id' => $childData['id']));
        $persistenceFacade->delete($childoid, $recursive);
      }
    }
    // postcondition: the node and all of its children are deleted from db
    return true;
  }
  /**
   * @see PersistenceMapper::getOIDs()
   * @note The orderby, pagingInfo parameters are not supported by this mapper.
   */
  function getOIDs($type, $criteria=null, $orderby=null, &$pagingInfo)
  {
    // use criteria condition if criteria is given
    $attribCondStr = "";
    if ($criteria != null)
    {
      $attribCondStr = "[@";
      foreach($criteria as $name => $value)
        $attribCondStr .= $name."='".$value."'][@";
      $attribCondStr = substr($attribCondStr, 0, strlen($attribCondStr)-strlen("[@"));
    }

    // auto start transaction
    $this->startReadTransaction();
  	//WCMFException::throwEx("No Connection. Start transaction first", __FILE__, __LINE__);

    // construct query
    $nodeQuery = "descendant::".$type.$attribCondStr;
    // execute query
    $oids = $this->_db->GetOIDs($nodeQuery);

    return $oids;
  }
  /**
   * @see PersistenceFacade::loadObjects()
   */
  function loadObjects($type, $buildDepth, $criteria=null, $orderby=null, &$pagingInfo, $buildAttribs=null, $buildTypes=null)
  {
    WCMFException::throwEx("Method NodeXMLDBMapper::loadObjects() is not implemented.", __FILE__, __LINE__);
  }
  /**
   * Start a non blocking read transaction
   */
  function startReadTransaction()
  {
    Log::debug("start read transaction on ".$this->_filename, __CLASS__);
    $this->openDatabase(false);
  }
  /**
   * @see PersistenceMapper::startTransaction()
   * From now on all calls to save() and delete() will be executed to a temporary tree
   * that will be saved by the call to commitTransaction().
   */
  function startTransaction()
  {
    if (!$this->_inTransaction)
    {
      Log::debug("start transaction on ".$this->_filename, __CLASS__);
      $this->openDatabase();
      $this->_inTransaction = true;
    }
  }
  /**
   * @see PersistenceMapper::commitTransaction()
   * Save the temporary tree.
   */
  function commitTransaction()
  {
    if ($this->_inTransaction)
    {
      Log::debug("end transaction on ".$this->_filename, __CLASS__);
      $this->closeDatabase();
      $this->_inTransaction = false;
    }
  }
  /**
   * @see PersistenceMapper::rollbackTransaction()
   * Nothing to do since the changes have to be explicitely committed.
   */
  function rollbackTransaction()
  {
  }
  /**
   * Open the XML database
   * @param lock True/False wether a lock is required or not
   */
  function openDatabase($lock=true)
  {
    // open database (create if not existing, read-write)
    if ($this->_db->DbFileName != $this->_filename)
    {
      if (!$this->_db->Open($this->_filename, TRUE, $lock))
      {
      	$this->_db = null;
        WCMFException::throwEx("Could not open XML input: ".$this->_filename, __FILE__, __LINE__);
      }
      chmod($this->_filename, 0775);
      $this->_db->XmlDb->setVerbose(0);
      Log::debug("opened ".$this->_filename, __CLASS__);
    }
  }
  /**
   * Close the XML database
   */
  function closeDatabase()
  {
    $result = $this->_db->Close();
    Log::debug("closed ".$this->_filename." with result ".$result, __CLASS__);
  }

  /**
   * TEMPLATE METHODS
   * Subclasses will override these to define their Node type.
   */

  /**
   * Factory method for the supported object type.
   * @note Subclasses must implement this method to define their object type.
   * @param type The type of object to create
   * @param oid The object id (maybe null)
   * @return A reference to the created object.
   */
  function &createObject($type, $oid=null)
  {
    WCMFException::throwEx("createObject() must be implemented by derived class: ".get_class($this), __FILE__, __LINE__);
  }
  /**
   * Get the Node type definition.
   * @note Subclasses will override this to define their Node type.
   * @param type The type of the Node
   * @return An assoziative array with unchangeable keys '_properties', '_datadef', '_children', '_data'. The keys itself hold the following structures:
   *
   * - @em _properties: An array of assoziative arrays with the keys 'name', 'value' for every property
   *          (e.g. array('visible' => 1))
   * - @em _datadef: An array of assoziative arrays with the keys 'name', 'app_data_type', 'db_data_type', 'default' plus application specific keys
   *          for every data item. All keys except 'name' and 'app_data_type' will become keys in the Nodes valueProperties array
   *          hold for each data item.
   *          (e.g. array('name' => 'title', 'db_data_type' => 'data_txt', 'app_data_type' => DATATYPE_ATTRIBUTE, 'default' => 'Hello World!')) @n
   *          Known attributes are:
   *          - @em default: The default value (will be set when creating a blank Node, see PersistenceMapper::create())
   *          - @em restrictions_match: A regular expression that the value must match (e.g. '[0-3][0-9]\.[0-1][0-9]\.[0-9][0-9][0-9][0-9]' for date values)
   *          - @em restrictions_not_match:  A regular expression that the value must NOT match
   *          - @em is_editable: true, false whether the value should be editable, see FormUtil::getInputControl()
   *          - @em input_type: The HTML input type for the value, see FormUtil::getInputControl()
   * - @em _children: An array of assoziative arrays with the keys 'type', 'minOccurs', 'maxOccurs', 'aggregation', 'composition' for every child
   *          (e.g. array('type' => 'textblock', 'minOccurs' => 0, 'maxOccurs' => 'unbounded', 'aggregation' => true, 'composition' => false))
   * - @em _data: An assoziative array where the keys are the data item names defined in the @em _datadef array
   *          (e.g. array('title' => 'Hello User!'))
   *          @note The @em _data array will be overridden with data provided by the db select. No need for definition at this point!
   */
  function getNodeDefinition($type) { return array(); }
}
?>

