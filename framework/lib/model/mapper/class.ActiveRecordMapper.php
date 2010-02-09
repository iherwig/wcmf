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
require_once(BASE."wcmf/lib/util/class.Log.php");
require_once(BASE."wcmf/lib/model/mapper/class.ActiveRecord.php");
require_once(BASE."wcmf/lib/persistence/class.AbstractMapper.php");
require_once(BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(BASE."wcmf/lib/persistence/class.AttributeDescription.php");
require_once(BASE."wcmf/lib/persistence/class.RelationDescription.php");
require_once(BASE."wcmf/lib/persistence/class.PersistenceException.php");
require_once(BASE."wcmf/lib/persistence/converter/class.DataConverter.php");
require_once(BASE."wcmf/3rdparty/adodb/adodb.inc.php");
require_once(BASE."wcmf/3rdparty/adodb/adodb-exceptions.inc.php");

/**
 * Global logging function
 */
$LAST_QUERY = '';
function &ActiveRecordMapperLogSql($db, $sql, $inputarray)
{
  global $LAST_QUERY;
  if (Log::isDebugEnabled('ActiveRecordMapper')) {
    Log::debug($sql."\n".WCMFException::getStackTrace(), 'ActiveRecordMapper');
  }
  $LAST_QUERY = $sql;
  $null = null;
  return $null;
}

/**
 * @class ActiveRecordMapper
 * @ingroup Mapper
 * @brief ActiveRecordMapper maps objects of one type to a relational database schema
 * using ADOdb's Active Records.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class ActiveRecordMapper extends AbstractMapper implements PersistenceMapper
{
  private $_connParams = null; // database connection parameters
  private $_conn = null;       // database connection
  private $_dbPrefix = '';     // database prefix (if given in the configuration file)

  /**
   * Constructor.
   * @param params Initialization data given in an assoziative array with the following keys:
   *               dbType, dbHostName, dbUserName, dbPassword, dbName OR dbConnection
   *               if dbPrefix is given it will be appended to every table string, which is
   *               usefull if different applications operate on the same database
   */
  public function __construct($params)
  {
    // store connection parameters to allow lazy connect
    $this->_connParams = $params;
    $this->_dataConverter = new DataConverter();
  }
  /**
   * Actually connect to the database using the parameters given to the constructor.
   */
  private function connect()
  {
    if (array_key_exists('dbType', $this->_connParams) && array_key_exists('dbHostName', $this->_connParams) &&
      array_key_exists('dbUserName', $this->_connParams) && array_key_exists('dbPassword', $this->_connParams) &&
      array_key_exists('dbName', $this->_connParams))
    {
      // create new connection
      try {
        $this->_conn = NewADOConnection($this->_connParams['dbType']);
        // we need to force a new connection so don't use Connect or PConnect
        $this->_conn->NConnect($this->_connParams['dbHostName'],
          $this->_connParams['dbUserName'],
          $this->_connParams['dbPassword'],
          $this->_connParams['dbName']);
      }
      catch(Exception $e) {
        throw new PersistenceException("Connection to ".$this->_connParams['dbHostName'].".".$this->_connParams['dbName']." failed: ".
          $e->getMessage());
      }
      $ADODB_COUNTRECS = false;
      define('ADODB_OUTP', "gError");

      // get database prefix if defined
      if (isset($this->_connParams['dbPrefix'])) {
        $this->_dbPrefix = $this->_connParams['dbPrefix'];
      }

      // log sql if requested
      $this->_conn->fnExecute = 'ActiveRecordMapperLogSql';

      // store connection for reuse
      $persistenceFacade = PersistenceFacade::getInstance();
      $persistenceFacade->storeConnection($this->_connParams, $this->_conn);
    }
    elseif (array_key_exists('dbConnection', $this->_connParams))
    {
      // use existing connection
      $this->_conn = &$this->_connParams['dbConnection'];
    }
    else {
      throw new IllegalArgumentException("Wrong parameters for constructor.");
    }
  }
  /**
   * Get a new id for inserting into the database.
   * @return An id value.
   */
  protected function ensureConnect()
  {
    if ($this->_conn == null) {
      $this->connect();
    }
  }
  /**
   * Get a new id for inserting into the database.
   * @return An id value.
   */
  private function getNextId()
  {
    $this->ensureConnect();
    $nextId = $this->_conn->GenID();
    return $nextId;
  }
  /**
   * Get the database connection.
   * @return A reference to the NewADOConnection object
   */
  public function getConnection()
  {
    $this->ensureConnect();
    return $this->_conn;
  }
  /**
   * Execute a query on the ADOdb connection.
   * Selects the optimal method depending on the command and logging.
   * @param sql The sql command
   * @param inputarr An array of bind variables [optional]
   * @return A ADORecordSet or false
   */
  public function executeSql($sql, $inputarr=false)
  {
    $this->ensureConnect();

    // check if we are logging
    if (Log::isDebugEnabled(__CLASS__)) {
      $fkt = 'Execute';
    }
    else
    {
      if (strpos(strtolower($sql), 'select') === 0) {
        $fkt = '_Execute';
      }
      else {
        $fkt = '_query';
      }
    }
    return $this->_conn->$fkt($sql, $inputarr);
  }
  /**
   * @see PersistenceMapper::getPkNames()
   */
  public function getPkNames()
  {
    $table = $this->getActiveRecord()->TableInfo();
    return array_key_exists($table->keys);
  }
  /**
   * @see PersistenceMapper::getRelations()
   */
  public function getRelations($relationType=null)
  {
    return array();
  }
  /**
   * @see PersistenceMapper::getRelation()
   */
  public function getRelation($roleName)
  {
    return new RelationDescription();
  }
  /**
   * @see PersistenceMapper::getAttributes()
   */
  public function getAttributes(array $dataTypes=array())
  {
    return array();
  }
  /**
   * @see PersistenceMapper::getAttribute()
   */
  public function getAttribute($name)
  {
    return new AttributeDescription();
  }
  /**
   * Check if a value is a primary key value
   * @param name The name of the value
   * @return True/False
   */
  protected function isPkValue($name)
  {
    return array_key_exists($name, $this->getPkNames());
  }
  /**
   * Construct an object id from given row data
   * @param type The type of object
   * @param data An associative array with the pk column names as keys and pk values as values
   * @return The oid
   */
  function constructOID($type, $data)
  {
    $pkNames = $this->getPKNamesForType($type);
    $params = array('type' => $type, 'id' => array());
    foreach ($pkNames as $pkName)
      array_push($params['id'], $data[$pkName]);
    return PersistenceFacade::composeOID($params);

  }
  /**
   * @see AbstractMapper::loadImpl()
   */
  function loadImpl(ObjectId $oid, $buildDepth, array $buildAttribs=null, array $buildTypes=null)
  {
    $persistenceFacade = &PersistenceFacade::getInstance();

    // delegate to loadObjects
    $criteria = $this->createPKCondition($oid);
    $pagingInfo = null;
    $objects = $this->loadObjects(PersistenceFacade::getOIDParameter($oid, 'type'), $buildDepth, $criteria, null,
                  $pagingInfo, $buildAttribs, $buildTypes);
    if (sizeof($objects) > 0)
      return $objects[0];
    else
      return null;
  }
  /**
   * @see AbstractMapper::createImpl()
   * @note The type parameter is not used here because this class only constructs one type.
   */
  function createImpl($type, $buildDepth, array $buildAttribs=null)
  {
    if ($buildDepth < 0 && !in_array($buildDepth, array(BUILDDEPTH_INFINITE, BUILDDEPTH_SINGLE, BUILDDEPTH_REQUIRED))) {
      WCMFException::throwEx("Build depth not supported: $buildDepth", __FILE__, __LINE__);
    }
    $persistenceFacade = &PersistenceFacade::getInstance();

    // get attributes to load
    $attribs = null;
    if ($buildAttribs != null && array_key_exists($this->getType(), $buildAttribs)) {
      $attribs = $buildAttribs[$this->getType()];
    }

    // create the object
    $object = &$this->createObjectFromData($attribs);

    // recalculate build depth for the next generation
    if ($buildDepth != BUILDDEPTH_REQUIRED && $buildDepth != BUILDDEPTH_SINGLE && $buildDepth != BUILDDEPTH_INFINITE && $buildDepth > 0) {
      $newBuildDepth = $buildDepth-1;
    }
    else {
      $newBuildDepth = $buildDepth;
    }

    // prevent infinite recursion
    if ($buildDepth < BUILDDEPTH_MAX)
    {
      // get object definition (default values, properties)
      $objectData = $this->getObjectDefinition();

      // set dependend objects of this object
      foreach ($objectData['_children'] as $childDef)
      {
        // in case of a many to many child, use the child definition at the other end of the relation
        $isManyToMany = $childDef['is_manyToMany'];
        if ($isManyToMany)
        {
          // get the other types role
          $otherEnd = $this->getOtherEndForManyToMany($childDef['role']);
          // override the many to many type, except for relation properties
          $childDef['type'] = $otherEnd['type'];
          $childDef['role'] = $otherEnd['role'];
          $childDef['table_name'] = $otherEnd['table_name'];
          $childDef['pk_columns'] = $otherEnd['pk_columns'];
          $childDef['fk_columns'] = '';
          $childDef['order_by'] = array();
          $childDef['is_manyToMany'] = false;
        }

        // set 'minOccurs', 'maxOccurs'
        if (!array_key_exists('minOccurs', $childDef))
          $childDef['minOccurs'] = 0; // default value
        if (!array_key_exists('maxOccurs', $childDef))
          $childDef['maxOccurs'] = 1; // default value

        if ( ($buildDepth != BUILDDEPTH_SINGLE) && (($buildDepth > 0) || ($buildDepth == BUILDDEPTH_INFINITE) ||
          (($buildDepth == BUILDDEPTH_REQUIRED) && $childDef['minOccurs'] > 0 && $childDef['aggregation'] == true)) )
        {
          if ($isManyToMany) {
            $childObject = &$persistenceFacade->create($childDef['type'], BUILDDEPTH_SINGLE, $buildAttribs);
          }
          else {
            $childObject = &$persistenceFacade->create($childDef['type'], $newBuildDepth, $buildAttribs);
          }

          $childObject->setProperty('minOccurs', $childDef['minOccurs']);
          $childObject->setProperty('maxOccurs', $childDef['maxOccurs']);
          $childObject->setProperty('aggregation', $childDef['aggregation']);
          $childObject->setProperty('composition', $childDef['composition']);
          $this->appendObject($object, $childObject, $childDef['role']);
        }
      }
    }

    return $object;
  }
  /**
   * @see AbstractMapperMapper::saveImpl()
   */
  function saveImpl(PersistentObject $object)
  {
    $this->ensureConnect();

    // prepare object data
    // escape all values (except for primary key values)
    $appValues = array();
    foreach ($object->getDataTypes() as $type)
    {
      foreach ($object->getValueNames($type) as $valueName)
      {
        if (!$this->isPkValue($valueName, $type))
        {
          $properties = $object->getValueProperties($valueName, $type);
          $appValues[$type][$valueName] = $object->getValue($valueName, $type);
          // NOTE: strip slashes from "'" and """ first because on INSERT/UPDATE we use ADODB's qstr
          // with second parameter false which will add slashes again
          // (we do this manually because we can't rely on get_magic_quotes_gpc())
          $value = str_replace(array("\'","\\\""), array("'", "\""), $appValues[$type][$valueName]);
          $object->setValue($valueName, $this->_dataConverter->convertApplicationToStorage($value, $properties['db_data_type'], $valueName), $type);
        }
      }
    }

    $persistenceFacade = &PersistenceFacade::getInstance();
    if ($object->getState() == STATE_NEW)
    {
      // insert new object
      $this->prepareInsert($object);
      $sqlArray = $this->getInsertSQL($object);
      foreach($sqlArray as $sqlStr)
        if ($this->executeSql($sqlStr) === false)
        {
          Log::error($this->_conn->ErrorMsg().". Your query was: ".$sqlStr, __CLASS__);
          WCMFException::throwEx("Error inserting object ".$object->getOID().". See log file for details.", __FILE__, __LINE__);
        }

      // log action
      $this->logAction($object);
    }
    else if ($object->getState() == STATE_DIRTY)
    {
      // save existing object
      // precondition: the object exists in the database

      // log action
      $this->logAction($object);

      // save object
      $sqlArray = $this->getUpdateSQL($object);
      foreach($sqlArray as $sqlStr)
        if ($this->executeSql($sqlStr) === false)
        {
          Log::error($this->_conn->ErrorMsg().". Your query was: ".$sqlStr, __CLASS__);
          WCMFException::throwEx("Error updating object ".$object->getOID().". See log file for details.", __FILE__, __LINE__);
        }
    }

    // set escaped values back to application values
    foreach ($object->getDataTypes() as $type)
      foreach ($object->getValueNames($type) as $valueName)
        if (!$this->isPkValue($valueName, $type))
          $object->setValue($valueName, $appValues[$type][$valueName], $type, true);

    $object->setState(STATE_CLEAN, false);
    // postcondition: the object is saved to the db
    //                the object state is STATE_CLEAN
    //                attributes are only inserted if their values differ from ''
    return true;
  }
  /**
   * @see AbstractMapper::deleteImpl()
   */
  function deleteImpl(ObjectId $oid, $recursive=true)
  {
    $this->ensureConnect();

    $persistenceFacade = &PersistenceFacade::getInstance();

    // log action
    if ($this->isLogging())
    {
      $obj = &$persistenceFacade->load($oid, BUILDDEPTH_SINGLE);
      if ($obj)
        $this->logAction($obj);
    }

    // delete object
    $sqlArray = $this->getDeleteSQL($oid);
    foreach($sqlArray as $sqlStr)
      if ($this->executeSql($sqlStr) === false)
      {
        Log::error($this->_conn->ErrorMsg().". Your query was: ".$sqlStr, __CLASS__);
        WCMFException::throwEx("Error deleting object '".$oid."'. See log file for details.", __FILE__, __LINE__);
      }

    // delete children
    if ($recursive)
    {
      // make sure that we only delete the composition children...
      $sqlArray = $this->getChildrenSelectSQL($oid);
      foreach($sqlArray as $childRole => $childDef)
      {
        $childoids = $persistenceFacade->getOIDs($childDef['type'], $childDef['criteria']);
        foreach($childoids as $childoid)
          $persistenceFacade->delete($childoid, $recursive);
      }
      // ...for the others we have to break the foreign key relation
      $sqlArray = $this->getChildrenDisassociateSQL($oid, true);
      foreach($sqlArray as $sqlStr)
      {
        if ($this->executeSql($sqlStr) === false)
        {
          Log::error($this->_conn->ErrorMsg().". Your query was: ".$sqlStr, __CLASS__);
          WCMFException::throwEx("Error disassociating object from '".$oid."'. See log file for details.", __FILE__, __LINE__);
        }
      }
    }
    // postcondition: the object and all dependend objects are deleted from db
    return true;
  }
  /**
   * @see PersistenceMapper::getOIDs()
   * @note The type parameter is not used here because this class only constructs one type
   */
  function getOIDs($type, array $criteria=null, array $orderby=null, PagingInfo $pagingInfo)
  {
    $oids = array();

    // create query (load only pk columns and no children oids)
    $type = $this->getType();
    $objects = $this->loadObjects($type, BUILDDEPTH_SINGLE, $criteria, $orderby, $pagingInfo, array($type => array()), array($type), false);

    // collect oids
    for ($i=0; $i<sizeof($objects); $i++)
      array_push($oids, $objects[$i]->getOID());

    return $oids;
  }
  /**
   * @see PersistenceFacade::loadObjects()
   * @note The type parameter is not used here because this class only constructs one type.
   * The additional parameter selectChildOIDs indicates wether to load the oids of the children
   * or not and is used internally only.
   */
  public function loadObjects($type, $buildDepth, array $criteria=null, array $orderby=null, PagingInfo $pagingInfo=null,
    array $buildAttribs=null, array $buildTypes=null, $selectChildOIDs=true)
  {
    if ($buildDepth < 0 && !in_array($buildDepth, array(BUILDDEPTH_INFINITE, BUILDDEPTH_SINGLE))) {
      throw new IllegalArgumentException("Build depth not supported: $buildDepth");
    }
    $this->ensureConnect();
    $objects = array();

    // check buildTypes
    if (is_array($buildTypes) && !in_array($this->getType(), $buildTypes)) {
      return $objects;
    }
    // get attributes to load
    $attribs = null;
    if ($buildAttribs != null && array_key_exists($this->getType(), $buildAttribs)) {
      $attribs = $buildAttribs[$this->getType()];
    }
    // create condition
    $attribCondStr = "";
    if ($criteria != null)
    {
      // criteria is an array
      if (is_array($criteria))
      {
        foreach($criteria as $name => $value) {
          $attribCondStr .= $name."=".$this->_conn->qstr($value)." AND ";
        }
        $attribCondStr = substr($attribCondStr, 0, strlen($attribCondStr)-strlen(" AND "));
      }
      else {
        $attribCondStr = $criteria;
      }
    }

    // create order
    $orderByStr = '';
    if ($orderby != null) {
      $orderByStr = join(', ', $orderby);
    }
    else
    {
      // use default ordering
      $orderByCols = $this->getOrderBy();
      if (is_array($orderByCols)) {
        $orderByStr = join(', ', $orderByCols);
      }
    }
    if (strlen($orderByStr) > 0) {
      $orderByStr = ' ORDER BY '.$orderByStr;
    }
    // create offset, limit
    $extra = array();
    if ($pagingInfo != null && $pagingInfo->getPageSize() > 0)
    {
      $extra['offset'] = $pagingInfo->getOffset();
      $extra['limit'] = $pagingInfo->getPageSize();
    }

    // find records
    $records = array();
    try {
      $records = $this->getActiveRecord()->Find($attribCondStr.$orderByStr, false, false, $extra);
    }
    catch (Exception $e) {
      throw new PersistenceException("Load objects failed failed: ".$e->getMessage());
    }

    // update pagingInfo
    if ($pagingInfo != null)
    {
      global $LAST_QUERY;
      $cnt = _adodb_getcount($this->_conn, $LAST_QUERY);
      $pagingInfo->setTotalCount($cnt);
    }

    if (sizeof($records) == 0) {
      return $objects;
    }

    // create PersistentObject instances from the retrieved records
    for ($i=0, $numObjects=sizeof($records); $i<$numObjects; $i++)
    {
      // create the object
      $object = &$this->createObjectFromData($attribs, $records[$i]);

      // append child data (childoids or real children depending on the buildDepth)
      if ($selectChildOIDs) {
        $this->appendChildData($object, $buildDepth, $buildAttribs, $buildTypes);
      }
      $object->setState(STATE_CLEAN, true);
      $objects[] = &$object;
    }
    return $objects;
  }
  /**
   * Create an object of the mapper's type with the given attributes from the given data
   * @param attribs An array of attributes to create
   * @param data The '_data' array contained in the array returned by getObjectDefinition @see RDBMapper::applyDataOnLoad()
   * @return A reference to the object
   */
  public function createObjectFromData($attribs, $data=null)
  {
    // determine if we are loading or creating
    $createFromLoadedData = false;
    if (is_array($data)) {
      $createFromLoadedData = true;
    }
    // get object definition (default values, properties)
    $objectData = $this->getObjectDefinition();

    // initialize data and oid
    if ($createFromLoadedData)
    {
      // fill the given data into the definition
      $objectData['_data'] = $data;
      $oid = $this->constructOID($this->getType(), $objectData['_data']);
    }
    else {
      $oid = null;
    }
    // construct object
    $object = $this->createObject($oid);

    // set object properties
    foreach($objectData['_properties'] as $property) {
      $object->setProperty($property['name'], $property['value']);
    }
    // apply data to the created object
    if ($createFromLoadedData) {
      $this->applyDataOnLoad($object, $objectData, $attribs);
    }
    else {
      $this->applyDataOnCreate($object, $objectData, $attribs);
    }
    return $object;
  }
  /**
   * Append the child data to an object. If the buildDepth does not determine to load a
   * child generation, only the oids of the children will be loaded.
   * @param object A reference to the object to append the children to
   * @param buildDepth @see PersistenceFacade::loadObjects()
   * @param buildAttribs @see PersistenceFacade::loadObjects()
   * @param buildTypes @see PersistenceFacade::loadObjects()
   */
  function appendChildData(&$object, $buildDepth, $buildAttribs=null, $buildTypes=null)
  {
    $persistenceFacade = &PersistenceFacade::getInstance();

    // recalculate build depth for the next generation
    if ($buildDepth != BUILDDEPTH_SINGLE && $buildDepth != BUILDDEPTH_INFINITE && $buildDepth > 0) {
      $newBuildDepth = $buildDepth-1;
    }
    else {
      $newBuildDepth = $buildDepth;
    }
    // get dependend objects of this object
    $childoids = array();
    $sqlArray = $this->getChildrenSelectSQL($object->getOID());
    foreach($sqlArray as $childRole => $childDef)
    {
      $oids = $persistenceFacade->getOIDs($childDef['type'], $childDef['criteria']);

      // if the child type is one to many, we use the oids directly
      $tmpChildDef = $this->getChildDefinition($childRole);
      if (!$tmpChildDef['is_manyToMany'])
      {
        $childoids[$childRole] = $oids;
      }
      else
      {
        // for many to many children, we have to load the association objects and collect the parent oids of the other type
        $childPagingInfo = null;
        $nmChildren = $persistenceFacade->loadObjects($childDef['type'], BUILDDEPTH_SINGLE, $childDef['criteria'], null, $childPagingInfo);

        // collect parent oids
        $poids = array();
        for ($i=0; $i<sizeof($nmChildren); $i++)
        {
          $parentOID = array_pop(array_diff($nmChildren[$i]->getParentOIDs(), array($object->getOID())));
          array_push($poids, $parentOID);
        }

        // get the other types role
        $otherEnd = $this->getOtherEndForManyToMany($childRole);
        $childoids[$otherEnd['role']] = $poids;
        $childRole = $otherEnd['role'];

        // override values for child select
        $childDef['type'] = $otherEnd['type'];

        $otherEndMapper = &$persistenceFacade->getMapper($otherEnd['type']);
        $criteria = '';
        foreach ($poids as $oid) {
          $criteria .= $otherEndMapper->createPKCondition($oid)." OR ";
        }
        $childDef['criteria'] = substr($criteria, 0, -4);
      }

      // load dependend objects if build depth is not satisfied already
      if (($buildDepth != BUILDDEPTH_SINGLE) && ($buildDepth > 0 || $buildDepth == BUILDDEPTH_INFINITE))
      {
        $childPagingInfo = null;
        $children = $persistenceFacade->loadObjects($childDef['type'], $newBuildDepth, $childDef['criteria'], null, $childPagingInfo, $buildAttribs, $buildTypes);
        for ($j=0; $j<sizeof($children); $j++) {
          $this->appendObject($object, $children[$j], $childRole);
        }
      }
    }
    $object->setProperty('childoids', $childoids);
  }
  /**
   * @see PersistenceMapper::startTransaction()
   */
  function startTransaction()
  {
    $this->ensureConnect();
    $this->_conn->StartTrans();
  }
  /**
   * @see PersistenceMapper::commitTransaction()
   */
  function commitTransaction()
  {
    $this->ensureConnect();
    $this->_conn->CompleteTrans();
  }
  /**
   * @see PersistenceMapper::rollbackTransaction()
   * @note Rollbacks have to be supported by the database.
   */
  function rollbackTransaction()
  {
    $this->ensureConnect();
    $this->_conn->FailTrans();
    $this->_conn->CompleteTrans();
  }

  /**
   * TEMPLATE METHODS
   * Subclasses must implement this method to define their object type.
   */

  /**
   * Factory method for the supported object type.
   * @param oid The object id (maybe null)
   * @return A reference to the created object.
   */
  abstract function createObject(ObjectId $oid=null);

  /**
   * Get the active record for the type.
   * @return An ActiveRecord instance.
   */
  abstract function getActiveRecord();

  /**
   * Add a dependend object (child) to an object.
   * @note Subclasses must implement this method to define their object type.
   * @param object The object to add to.
   * @param dependendObject The object to add.
   * @param role The role of the dependent object in relation to the object. If null, the role is the type [default: null]
   */
  function appendObject(&$object, &$dependendObject, $role=null)
  {
    WCMFException::throwEx("appendObject() must be implemented by derived class: ".get_class($this), __FILE__, __LINE__);
  }
  /**
   * Apply the loaded object data to the object.
   * @note Subclasses must implement this method to define their object type.
   * @param object A reference to the object created with createObject method to which the data should be applied
   * @param objectData The array returned by the getObjectDefinition method with the '_data' array filled with
   *                   the rows returned by execution of the database select statement (given by getSelectSQL). These
   *                   rows hold the loaded data.
   * @param attribs The build attributes for the type of object (given in the buildAttribs parameter of the loadImpl method)
   */
  function applyDataOnLoad(&$object, $objectData, $attribs)
  {
    WCMFException::throwEx("applyDataOnLoad() must be implemented by derived class: ".get_class($this), __FILE__, __LINE__);
  }
  /**
   * Apply the default data to the object.
   * @note Subclasses must implement this method to define their object type.
   * @param object A reference to the object created with createObject method to which the data should be applied
   * @param objectData The array returned by the getObjectDefinition method. The default data are extracted from this.
   * @param attribs The build attributes for the type of object (given in the buildAttribs parameter of the loadImpl method).
   */
  function applyDataOnCreate(&$object, $objectData, $attribs)
  {
    WCMFException::throwEx("applyDataOnCreate() must be implemented by derived class: ".get_class($this), __FILE__, __LINE__);
  }
  /**
   * Set the object primary key values for inserting the object to the database.
   * @note Subclasses must implement this method to define their object type.
   * @param object A reference to the object to insert.
   * @note The object does not have the final object id set. If a new id value for a primary key column is needed
   * for the insert statement, use RDBMapper::getNextId().
   */
  function prepareInsert(&$object)
  {
    WCMFException::throwEx("prepareInsert() must be implemented by derived class: ".get_class($this), __FILE__, __LINE__);
  }
  /**
   * Get the object type this mapper supports.
   * @note Subclasses must implement this method to define their object type.
   * @return The name of the supported object type.
   */
  function getType()
  {
    WCMFException::throwEx("getType() must be implemented by derived class: ".get_class($this), __FILE__, __LINE__);
  }
  /**
   * Get the object type definition.
   * @note Subclasses must implement this method to define their object type.
   * @return An assoziative array with unchangeable keys '_properties', '_attributes', '_relations' plus application specific keys.
   * The predefined keys hold the following structures:
   *
   * - @em _properties: An assoziative array with the property names as keys and the property values as values
   *          (e.g. array('display_value' => 'name', ...)
   * - @em _attributes: An assoziative array with the attribute names as keys and AttributeDescription instances as values
   * - @em _relations: An assoziative array with the relation (role) names as keys and RelationDescription instances as values
   */
  abstract function getObjectDefinition();

  /**
   * Get the names of the columns to use in order by, when retrieving the oids.
   * @return An array of column names
   */
  abstract function getOrderBy();
}
?>