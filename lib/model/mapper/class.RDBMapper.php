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
require_once(BASE."wcmf/lib/persistence/class.PersistenceMapper.php");
require_once(BASE."wcmf/lib/persistence/class.AbstractMapper.php");
require_once(BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(BASE."wcmf/lib/persistence/class.PersistentObjectProxy.php");
require_once(BASE."wcmf/lib/persistence/converter/class.DataConverter.php");
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
 * Global logging function
 */
function &logSql($db, $sql, $inputarray)
{
  Log::info($sql."\n".WCMFException::getStackTrace(), __FILE__, __LINE__);
  $null = null;
  return $null;
}

/**
 * @class RDBMapper
 * @ingroup Mapper
 * @brief RDBMapper maps objects of one type to a relational database schema.
 * It defines a persistence mechanism that specialized mappers customize by overriding
 * the given template methods.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class RDBMapper extends AbstractMapper implements PersistenceMapper
{
  private $_connParams = null; // database connection parameters
  protected $_conn = null;       // database connection
  protected $_dbPrefix = '';     // database prefix (if given in the configuration file)

  private $_relations = null;
  private $_attributes = null;

  /**
   * Constructor.
   * @param params Initialization data given in an assoziative array with the following keys:
   *               dbType, dbHostName, dbUserName, dbPassword, dbName OR dbConnection
   *               if dbPrefix is given it will be appended to every table string, which is
   *               usefull if different cms operate on the same database
   */
  public function __construct($params)
  {
    // store connection parameters to allow lazy connect
    $this->_connParams = &$params;
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
      $this->_conn = ADONewConnection($this->_connParams['dbType']);
      // we need to force a new connection so don't use Connect or PConnect
      $connected = $this->_conn->NConnect($this->_connParams['dbHostName'],
        $this->_connParams['dbUserName'],
        $this->_connParams['dbPassword'],
        $this->_connParams['dbName']);
      if (!$connected) {
        throw new PersistenceException("Connection to ".$this->_connParams['dbHostName'].".".
          $this->_connParams['dbName']." failed: ".$this->_conn->ErrorMsg());
      }
      $this->_conn->replaceQuote = "\'";
      $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
      $ADODB_COUNTRECS = false;
      define('ADODB_OUTP', "gError");

      // get database prefix if defined
      if (array_key_exists('dbPrefix', $this->_connParams)) {
        $this->_dbPrefix = $this->_connParams['dbPrefix'];
      }

      // log sql if requested
      $parser = InifileParser::getInstance();
      if (($logging = $parser->getValue('logSQL', 'cms')) === false) {
        $logging = 0;
      }
      if ($logging) {
        $this->_conn->fnExecute = 'logSql';
      }
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
      throw new InvalidArgumentException("Wrong parameters for constructor.", __FILE__, __LINE__);
    }
  }
  /**
   * Get a new id for inserting into the database
   * @return An id value.
   */
  private function getNextId()
  {
    $nextId = $this->_conn->GenID();
    return $nextId;
  }
  /**
   * Execute a query on the adodb connection.
   * Selects the optimal method depending on the command and logging.
   * @param sql The sql command
   * @return A ADORecordSet or false
   */
  public function executeSql($sql)
  {
    // check if we are logging
    if ($this->_conn->fnExecute == 'logSql')
      $fkt = 'Execute';
    else
    {
      if (strpos(strtolower($sql), 'select') === 0)
        $fkt = '_Execute';
      else
        $fkt = '_query';
    }
    return $this->_conn->$fkt($sql);
  }
  /**
   * @see PersistenceMapper::getRelations()
   */
  public function getRelations($hierarchyType='all')
  {
    $this->initRelations();
    if ($hierarchyType == 'all') {
      return array_values($this->_relations['byrole']);
    }
    else {
      return $this->_relations[$hierarchyType];
    }
  }
  /**
   * @see PersistenceMapper::getRelation()
   */
  public function getRelation($roleName)
  {
    $this->initRelations();
    if (array_key_exists($roleName, $this->_relations['byrole'])) {
      return $this->_relations['byrole'][$roleName];
    }
    else {
      throw new PersistenceException("No relation to '".$roleName."' exists in '".$this->getType()."'");
    }
  }
  /**
   * Get the relation descriptions defined in the subclass and add them to internal arrays.
   */
  private function initRelations()
  {
    if ($this->_relations == null) {
      $this->_relations = array();
      $this->_relations['byrole'] = $this->getRelationDescriptions();
      $this->_relations['parent'] = array();
      $this->_relations['child'] = array();
      $this->_relations['undefined'] = array();
      foreach ($this->_relations['byrole'] as $role => $desc) {
        if ($desc->hierarchyType == 'parent') {
          array_push($this->_relations['parent'], $desc);
        }
        elseif ($desc->hierarchyType == 'child') {
          array_push($this->_relations['child'], $desc);
        }
        else {
          array_push($this->_relations['undefined'], $desc);
        }
      }
    }
  }
  /**
   * @see PersistenceMapper::getAttributes()
   */
  public function getAttributes(array $dataTypes=array())
  {
    $this->initAttributes();
    if (sizeof($dataTypes) == 0) {
      return array_values($this->_attributes['byname']);
    }
    else
    {
      $result = array();
      foreach ($this->_attributes['byname'] as $name => $desc)
      {
        if (sizeof(array_diff($dataTypes, $desc->appDataTypes)) > 0) {
          array_push($result, $desc);
        }
      }
      return $result;
    }
  }
  /**
   * @see PersistenceMapper::getAttribute()
   */
  public function getAttribute($name)
  {
    $this->initAttributes();
    if (array_key_exists($name, $this->_attributes['byname'])) {
      return $this->_attributes['byname'][$name];
    }
    else {
      throw new PersistenceException("No attribute '".$name."' exists in '".$this->getType()."'");
    }
  }
  /**
   * Get the relation descriptions defined in the subclass and add them to internal arrays.
   */
  private function initAttributes()
  {
    if ($this->_attributes == null) {
      $this->_attributes = array();
      $this->_attributes['byname'] = $this->getAttributeDescriptions();
    }
  }
  /**
   * Check if a value is a primary key value
   * @param name The name of the value
   * @return True/False
   */
  protected function isPkValue($name)
  {
    $pkNames = $this->getPKNames();
    return in_array($name, $pkNames);
  }
  /**
   * Construct an object id from given row data
   * @param type The type of object
   * @param data An associative array with the pk column names as keys and pk values as values
   * @return The oid
   */
  private function constructOID($type, $data)
  {
    $pkNames = $this->getPKNamesForType($type);
    $ids = array();
    foreach ($pkNames as $pkName) {
      array_push($ids, $data[$pkName]);
    }
    return new ObjectId($type, $ids);

  }
  /**
   * Get the pk column names for a given type
   * @param type The type of object
   * @return An array of column names
   */
  private function getPKNamesForType($type)
  {
    $persistenceFacade = PersistenceFacade::getInstance();
    $mapper = $persistenceFacade->getMapper($type);
    return $mapper->getPkNames();

  }
  /**
   * @see PersistenceMapper::loadImpl()
   */
  protected function loadImpl(ObjectId $oid, $buildDepth, array $buildAttribs=array(), array $buildTypes=array())
  {
    // delegate to loadObjects
    $criteria = $this->createPKCondition($oid);
    $pagingInfo = null;
    $objects = $this->loadObjects($oid->getType(), $buildDepth, $criteria, null,
                  $pagingInfo, $buildAttribs, $buildTypes);
    if (sizeof($objects) > 0)
      return $objects[0];
    else
      return null;
  }
  /**
   * @see PersistenceMapper::createImpl()
   * @note The type parameter is not used here because this class only constructs one type
   */
  protected function createImpl($type, $buildDepth, array $buildAttribs=array())
  {
    if ($buildDepth < 0 && !in_array($buildDepth, array(BUILDDEPTH_INFINITE, BUILDDEPTH_SINGLE, BUILDDEPTH_REQUIRED))) {
      throw new InvalidArgumentException("Build depth not supported: $buildDepth", __FILE__, __LINE__);
    }
    $persistenceFacade = &PersistenceFacade::getInstance();

    // get attributes to load
    $attribs = array();
    if (sizeof($buildAttribs) > 0 && array_key_exists($this->getType(), $buildAttribs)) {
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
   * @see PersistenceMapper::saveImpl()
   */
  protected function saveImpl(PersistentObject $object)
  {
    if ($this->_conn == null)
      $this->connect();

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
          throw new PersistenceException("Error inserting object ".$object->getOID().". See log file for details.");
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
          throw new PersistenceException("Error updating object ".$object->getOID().". See log file for details.");
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
   * @see PersistenceMapper::deleteImpl()
   */
  protected function deleteImpl(ObjectId $oid, $recursive=true)
  {
    if ($this->_conn == null)
      $this->connect();

    $persistenceFacade = PersistenceFacade::getInstance();

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
    {
      if ($this->executeSql($sqlStr) === false)
      {
        Log::error($this->_conn->ErrorMsg().". Your query was: ".$sqlStr, __CLASS__);
        throw new PersistenceException("Error deleting object '".$oid."'. See log file for details.");
      }
    }
    // delete children
    if ($recursive)
    {
      // make sure that we only delete the composition children...
      $sqlArray = $this->getChildrenSelectSQL($oid);
      foreach($sqlArray as $childRole => $childDef)
      {
        $attribs = $this->getPkNamesForType($childDef['type']);
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
          throw new PersistenceException("Error disassociating object from '".$oid."'. See log file for details.");
        }
      }
    }
    // postcondition: the object and all dependend objects are deleted from db
    return true;
  }
  /**
   * Get the database connection.
   * @return A reference to the ADONewConnection object
   */
  protected function getConnection()
  {
    if ($this->_conn == null) {
      $this->connect();
    }
    return $this->_conn;
  }
  /**
   * @see PersistenceMapper::getOIDs()
   * @note The type parameter is not used here because this class only constructs one type
   */
  public function getOIDs($type, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null)
  {
    $oids = array();

    // create query (load only pk columns and no children oids)
    $type = $this->getType();
    $objects = $this->loadObjectsImpl($type, BUILDDEPTH_SINGLE, $criteria, $orderby, $pagingInfo, array($type => array()), array($type), false);

    // collect oids
    for ($i=0; $i<sizeof($objects); $i++)
      array_push($oids, $objects[$i]->getOID());

    return $oids;
  }
  /**
   * @see PersistenceFacade::loadObjects()
   */
  public function loadObjects($type, $buildDepth, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null,
    array $buildAttribs=array(), array $buildTypes=array())
  {
    return $this->loadObjectsImpl($type, $buildDepth, $criteria, $orderby, $pagingInfo, $buildAttribs, $buildTypes);
  }
  /**
   * @see PersistenceFacade::loadObjects()
   * @note The additional parameter selectChildOIDs indicates wether to load the oids of the children or not and is
   * used internally only [default: true]
   */
  private function loadObjectsImpl($type, $buildDepth, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null,
    array $buildAttribs=array(), array $buildTypes=array(), $selectChildOIDs=true)
  {
    if ($buildDepth < 0 && !in_array($buildDepth, array(BUILDDEPTH_INFINITE, BUILDDEPTH_SINGLE))) {
      throw new IllegalArgumentException("Build depth not supported: $buildDepth", __FILE__, __LINE__);
    }
    if ($this->_conn == null) {
      $this->connect();
    }
    $objects = array();

    // check buildTypes
    if (sizeof($buildTypes) > 0 && !in_array($type, $buildTypes)) {
      return $objects;
    }
    // get attributes to load
    $attribs = array();
    if (sizeof($buildAttribs) > 0 && array_key_exists($this->getType(), $buildAttribs)) {
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
    $orderbyStr = "";
    if ($orderby != null) {
      $orderbyStr = join(', ', $orderby);
    }
    // create query
    $data = array();
    $sqlStr = $this->getSelectSQL($attribCondStr, null, $orderbyStr);
    if ($pagingInfo != null && $pagingInfo->getPageSize() > 0) {
      $rs = &$this->_conn->PageExecute($sqlStr, $pagingInfo->getPageSize(), $pagingInfo->getPage());
    }
    else {
      $rs = $this->executeSql($sqlStr);
    }

    if (!$rs) {
      Log::error($this->_conn->ErrorMsg().". Your query was: ".$sqlStr, __CLASS__);
      throw new PersistenceException("Error loading objects. See log file for details.");
    }
    else
    {
      // update pagingInfo
      if ($pagingInfo != null) {
        $pagingInfo->setTotalCount($rs->MaxRecordCount());
      }
      while ($row = $rs->FetchRow()) {
        $data[] = $row;
      }
      $rs->Close();

      if (sizeof($data) == 0) {
        return $objects;
      }
    }

    $numObjects = sizeof($data);
    for ($i=0; $i<$numObjects; $i++)
    {
      // create the object
      $object = $this->createObjectFromData($attribs, $data[$i]);

      // append child data (childoids or real children depending on the buildDepth)
      if ($selectChildOIDs) {
        $this->appendRelationData($object, $buildDepth, $buildAttribs, $buildTypes);
      }
      $object->setState(STATE_CLEAN, true);
      $objects[] = $object;
    }
    return $objects;
  }
  /**
   * Create an object of the mapper's type with the given attributes from the given data
   * @param attribs An array of attributes to create. Empty array, if all attributes should be created [default: empty array]
   * @param data An associative array with the attribute names as keys and the attribute values as values [default: empty array]
   * @return A reference to the object
   */
  protected function createObjectFromData(array $attribs=array(), array $data=array())
  {
    // determine if we are loading or creating
    $createFromLoadedData = false;
    if (sizeof($data) > 0) {
      $createFromLoadedData = true;
    }

    // initialize data and oid
    if ($createFromLoadedData) {
      $oid = $this->constructOID($this->getType(), $data);
    }
    else {
      $oid = null;
    }
    // construct object
    $object = $this->createObject($oid);

    // apply data to the created object
    if ($createFromLoadedData) {
      $this->applyDataOnLoad($object, $data, $attribs);
    }
    else {
      $this->applyDataOnCreate($object, $data, $attribs);
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
  protected function appendRelationData(PersistentObject $object, $buildDepth, array $buildAttribs=array(), array $buildTypes=array())
  {
    $persistenceFacade = PersistenceFacade::getInstance();

    // recalculate build depth for the next generation
    if ($buildDepth != BUILDDEPTH_SINGLE && $buildDepth != BUILDDEPTH_INFINITE && $buildDepth > 0) {
      $newBuildDepth = $buildDepth-1;
    }
    else {
      $newBuildDepth = $buildDepth;
    }
    $loadNextGeneration = (($buildDepth != BUILDDEPTH_SINGLE) && ($buildDepth > 0 || $buildDepth == BUILDDEPTH_INFINITE));

    // get dependend objects of this object
    $sqlArray = $this->getRelationSelectSQL($object);
    foreach($sqlArray as $role => $relativeDef)
    {
      $relDesc = $this->getRelation($role);
      $relatives = array();

      // if the build depth is not satisfied already we load the complete objects add them
      if ($loadNextGeneration)
      {
        $tmp = null;
        // if the relation is a many to many relation, we have to load the relation object and add the other side's instances
        if ($relDesc instanceof RDBManyToManyRelationDescription)
        {
          $nmObjects = $persistenceFacade->loadObjects($relativeDef['type'], BUILDDEPTH_SINGLE, $relativeDef['criteria'], null, $tmp, $buildAttribs, $buildTypes);
          $otherEndRole = $relDesc->otherEndRelation->otherRole;

          for ($i=0, $countI=sizeof($nmObjects); $i<$countI; $i++)
          {
            $otherEndObjs = $nmObjects[$i]->getValue($otherEndRole);
            if (is_array($otherEndObjs))
            {
              for ($j=0, $countJ=sizeof($otherEndObjs); $j<$countJ; $j++)
              {
                $otherEndObj = $otherEndObjs[$j];
                if (!$otherEndObj instanceof PersistentObjectProxy) {
                  throw new ErrorException("PersistentObjectProxy instance expected");
                }
                if ($loadNextGeneration) {
                  array_push($relatives, $otherEndObj->getRealSubject());
                }
                else {
                  array_push($relatives, $otherEndObj);
                }
              }
            }
          }
        }
        else {
          $relatives = $persistenceFacade->loadObjects($relativeDef['type'], $newBuildDepth, $relativeDef['criteria'], null, $tmp, $buildAttribs, $buildTypes);
        }
      }
      // otherwise only add proxies for the relation objects
      else
      {
        $oids = $persistenceFacade->getOIDs($relativeDef['type'], $relativeDef['criteria']);
        foreach ($oids as $oid) {
          array_push($relatives, new PersistentObjectProxy($oid));
        }
      }
      // set the value
      $object->setValue($role, $relatives);
    }
  }
  /**
   * Get the child definition for a many to many child
   */
  /**
   * @see PersistenceMapper::startTransaction()
   */
  public function startTransaction()
  {
    if ($this->_conn == null) {
      $this->connect();
    }
    $this->_conn->StartTrans();
  }
  /**
   * @see PersistenceMapper::commitTransaction()
   */
  public function commitTransaction()
  {
    if ($this->_conn == null) {
      $this->connect();
    }
    $this->_conn->CompleteTrans();
  }
  /**
   * @see PersistenceMapper::rollbackTransaction()
   * @note Rollbacks have to be supported by the database.
   */
  public function rollbackTransaction()
  {
    if ($this->_conn == null) {
      $this->connect();
    }
    $this->_conn->FailTrans();
    $this->_conn->CompleteTrans();
  }

  /**
   * TEMPLATE METHODS
   * Subclasses must implement this method to define their object type.
   */

  /**
   * Factory method for the supported object type.
   * @note Subclasses must implement this method to define their object type.
   * @param oid The object id (maybe null)
   * @return A reference to the created object.
   */
  abstract protected function createObject(ObjectId $oid=null);
  /**
   * Add a dependend object (child) to an object.
   * @note Subclasses must implement this method to define their object type.
   * @param object The object to add to.
   * @param dependendObject The object to add.
   * @param role The role of the dependent object in relation to the object. If null, the role is the type [default: null]
   */
  abstract protected function appendObject(PersistentObject $object, PersistentObject $dependendObject, $role=null);
  /**
   * Apply the loaded object data to the object.
   * @note Subclasses must implement this method to define their object type.
   * @param object A reference to the object created with createObject method to which the data should be applied
   * @param objectData An associative array with the data returned by execution of the database select statement
   * 			(given by getSelectSQL).
   * @param attribs The build attributes for the type of object (given in the buildAttribs parameter of the loadImpl method)
   */
  abstract protected function applyDataOnLoad(PersistentObject $object, array $objectData, array $attribs);
  /**
   * Apply the default data to the object.
   * @note Subclasses must implement this method to define their object type.
   * @param object A reference to the object created with createObject method to which the data should be applied
   * @param attribs The build attributes for the type of object (given in the buildAttribs parameter of the loadImpl method).
   */
  abstract protected function applyDataOnCreate(PersistentObject $object, array $attribs);
  /**
   * Set the object primary key values for inserting the object to the database.
   * @note Subclasses must implement this method to define their object type.
   * @param object A reference to the object to insert.
   * @note The object does not have the final object id set. If a new id value for a primary key column is needed
   * for the insert statement, use RDBMapper::getNextId().
   */
  abstract protected function prepareInsert(PersistentObject $object);
  /**
   * Get a list of all RelationDescriptions.
   * @return An associative array with the relation names as keys and the RelationDescription instances as values.
   */
  abstract protected function getRelationDescriptions();
  /**
   * Get a list of all AttributeDescriptions.
   * @return An associative array with the attribute names as keys and the AttributeDescription instances as values.
   */
  abstract protected function getAttributeDescriptions();
  /**
   * Get the SQL command to select object data from the database.
   * @note Subclasses must implement this method to define their object type.
   * @param condStr The condition string (without 'WHERE').
   * @param alias The alias for the table name (default: null uses none).
   * @param orderStr The order string (without 'ORDER BY') (default: null uses default order).
   * @param attribs An array listing the attributes to load (default: null loads all attributes).
   * @param asArray True to get an associative array with keys 'attributeStr', 'tableStr', 'conditionStr', 'orderStr'
   *        and the appropriate query parts as value. [default: false]
   * @return A SQL command that selects all object data that match the condition or an array with the query parts.
   * Additionally columns 'ptype0' ('ptype1', ...), 'prole0' ('prole1', ...) and 'pid0' ('pid1', ...) that define the objects parent are expected.
   * @note The names of the data item columns MUST match the data item names provided in the '_datadef' array from RDBMapper::getObjectDefinition()
   *       Use alias names if not! The selected data will be put into the '_data' array of the object definition.
   */
  abstract protected function getSelectSQL($condStr, $alias=null, $orderStr=null, $attribs=null, $asArray=false);
  /**
   * Get the SQL commands to select the object's relatives from the database.
   * @note Subclasses must implement this method to define their object type.
   * @param object The object to load the relatives for.
   * @param compositionOnly True/False indicates wether to only select composition relatives or all [default: false].
   * @return An associative array with the relative roles as keys and an associative array with keys 'type' and 'criteria' as values,
   *       where 'type' is the relative type and 'criteria' is the corresponding SQL condition to be used as criteria parameter
   *       in PersistenceFacade::loadObjects().
   */
  abstract protected function getRelationSelectSQL(PersistentObject $object, $compositionOnly=false);
  /**
   * Get the SQL command to disassociate the object's children from the object (e.g. setting the foreign key to null).
   * @note Subclasses must implement this method to define their object type.
   * @param oid The object id of the object to disassociate the children from.
   * @param sharedOnly True/False indicates wether to only disassociate shared children or all [default: false].
   * @return An array of SQL commands that disassociate all object children.
   */
  abstract protected function getChildrenDisassociateSQL(ObjectId $oid, $sharedOnly=false);
  /**
   * Get the SQL command to insert a object into the database.
   * @note Subclasses must implement this method to define their object type.
   * @param object A reference to the object to insert.
   * @return An array of SQL commands that insert a new object.
   */
  abstract protected function getInsertSQL(PersistentObject $object);
  /**
   * Get the SQL command to update a object in the database.
   * @note Subclasses must implement this method to define their object type.
   * @param object A reference to the object to update.
   * @return An array of SQL commands that update an existing object.
   */
  abstract protected function getUpdateSQL(PersistentObject $object);
  /**
   * Get the SQL command to delete a object from the database.
   * @note Subclasses must implement this method to define their object type.
   * @param oid The object id of the object to delete.
   * @return An array of SQL commands that delete an existing object.
   */
  abstract protected function getDeleteSQL(ObjectId $oid);
  /**
   * Create a condition string for the primary key values of the form id1=val1, id2=val2, ...
   * @note Subclasses must implement this method to define their object type.
   * @param oid The object id that defines the primary key values
   * @return The string
   */
  abstract protected function createPKCondition(ObjectId $oid);
}
?>
