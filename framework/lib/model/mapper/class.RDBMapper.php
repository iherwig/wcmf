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
require_once(WCMF_BASE."wcmf/lib/util/class.Log.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceMapper.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.AbstractMapper.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistentObjectProxy.php");
require_once(WCMF_BASE."wcmf/lib/persistence/converter/class.DataConverter.php");
require_once(WCMF_BASE."wcmf/lib/util/class.InifileParser.php");

require_once('Zend/Db.php');

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
  private static $SEQUENCE_CLASS = 'Adodbseq';
  
  private $_connParams = null; // database connection parameters
  private $_conn = null;       // database connection
  private $_dbPrefix = '';     // database prefix (if given in the configuration file)
  private $_delimiter = '`';   // delimiter for identifiers such as column/table names

  private $_relations = null;
  private $_attributes = null;

  // prepared statements
  private $_idSelectStmt = null;
  private $_idInsertStmt = null;
  private $_idUpdateStmt = null;

  /**
   * Constructor.
   * @param params Initialization data given in an assoziative array with the following keys:
   *               dbType, dbHostName, dbUserName, dbPassword, dbName
   *               if dbPrefix is given it will be appended to every table string, which is
   *               usefull if different cms operate on the same database
   */
  public function __construct($params)
  {
    // store connection parameters to allow lazy connect
    $this->_connParams = $params;
    $this->_dataConverter = null;
  }
  /**
   * Select data to be stored in the session.
   * PDO throws an excetption if tried to be (un-)serialized.
   */
  function __sleep()
  {
    return array('_connParams', '_dbPrefix');
  }
  /**
   * Actually connect to the database using the parameters given to the constructor.
   */
  private function connect()
  {
    // connect
    if (isset($this->_connParams['dbType']) && isset($this->_connParams['dbHostName']) &&
      isset($this->_connParams['dbUserName']) && isset($this->_connParams['dbPassword']) &&
      isset($this->_connParams['dbName']))
    {
      try {
        // create new connection
        $pdoParams = array(
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        );
        $params = array(
          'host' => $this->_connParams['dbHostName'],
          'username' => $this->_connParams['dbUserName'],
          'password' => $this->_connParams['dbPassword'],
          'dbname' => $this->_connParams['dbName'],
          'driver_options' => $pdoParams
        );
        if (!empty($this->_connParams['dbPort'])) {
          $params['port'] = $this->_connParams['dbPort'];
        }
        $this->_conn = Zend_Db::factory('Pdo_'.ucfirst($this->_connParams['dbType']), $params);
      }
      catch(Exception $ex) {
        throw new PersistenceException("Connection to ".$this->_connParams['dbHostName'].".".
          $this->_connParams['dbName']." failed: ".$ex->getMessage());
      }

      // get database prefix if defined
      if (isset($this->_connParams['dbPrefix'])) {
        $this->_dbPrefix = $this->_connParams['dbPrefix'];
      }
    }
    else {
      throw new InvalidArgumentException("Wrong parameters for constructor.", __FILE__, __LINE__);
    }
  }
  /**
   * Get a new id for inserting into the database
   * @return An id value.
   */
  protected function getNextId()
  {
    try {
      $id = 0;
      if ($this->_idSelectStmt == null) {
        $this->_idSelectStmt = $this->_conn->prepare("SELECT id FROM ".$this->getSequenceTablename());
      }
      if ($this->_idInsertStmt == null) {
        $this->_idInsertStmt = $this->_conn->prepare("INSERT INTO ".$this->getSequenceTablename()." (id) VALUES (0)");
      }
      if ($this->_idUpdateStmt == null) {
        $this->_idUpdateStmt = $this->_conn->prepare("UPDATE ".$this->getSequenceTablename()." SET id=LAST_INSERT_ID(id+1);");
      }
      $this->_idSelectStmt->execute();
      $rows = $this->_idSelectStmt->fetchAll(PDO::FETCH_ASSOC);
      if (sizeof($rows) == 0) {
        $this->_idInsertStmt->execute();
        $this->_idInsertStmt->closeCursor();
        $row = array(array('id' => 0));
      }
      $id = $rows[0]['id'];
      $this->_idUpdateStmt->execute();
      $this->_idUpdateStmt->closeCursor();
      $this->_idSelectStmt->closeCursor();
      return $id;
    }
    catch (Exception $ex) {
      Log::error("The query: ".$sql."\ncaused the following exception:\n".$ex->getMessage(), __CLASS__);
      throw new PersistenceException("Error in persistent operation. See log file for details.");
    }
  }
  /**
   * Get the name of the sequence table
   * @return The name.
   */
  protected function getSequenceTablename()
  {
    
    $persistenceFacade = PersistenceFacade::getInstance();
    $mapper = $persistenceFacade->getMapper(self::$SEQUENCE_CLASS);
    if ($mapper instanceof RDBMapper) {
      return $mapper->getTableName();
    }
    else {
      throw new PersistenceException(self::$SEQUENCE_CLASS." is nor mapped by RDBMapper.");
    }
  }
  /**
   * Get the delimiter for identifiers such as column/table names
   * @return The delimiter character
   */
  protected function getDelimiter()
  {
    return $this->_delimiter;
  }
  /**
   * @see PersistenceMapper::quoteIdentifier
   */
  public function quoteIdentifier($identifier)
  {
    if (strpos($identifier, $this->_delimiter) !== 0) {
      return $this->_delimiter.$identifier.$this->_delimiter;
    }
    else {
      return $identifier;
    }
  }
  /**
   * Get the table name with the dbprefix added
   * @return The table name
   */
  public function getRealTableName()
  {
    return $this->_dbPrefix.$this->getTableName();
  }
  /**
   * Execute a query on the connection.
   * @param sql The sql command
   * @param isSelect True/False wether the statement is a select statement (default: false)
   * @return If isSelect is true, an array as the result of PDOStatement::fetchAll(PDO::FETCH_ASSOC),
   * the number of affected rows else
   */
  public function executeSql($sql, $isSelect=false)
  {
    if ($this->_conn == null)
      $this->connect();

    try {
      if ($isSelect) {
        $stmt = $this->_conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        $stmt->closeCursor();
        return $result;
      }
      else {
        return $this->_conn->exec($sql);
      }
    }
    catch (Exception $ex) {
      Log::error("The query: ".$sql."\ncaused the following exception:\n".$ex->getMessage(), __CLASS__);
      throw new PersistenceException("Error in persistent operation. See log file for details.");
    }
  }
  /**
   * Execute a select query on the connection.
   * @param sql The sql command
   * @param pagingInfo An PagingInfo instance describing which page to load
   * @return An array as the result of PDOStatement::fetchAll(PDO::FETCH_ASSOC)
   */
  public function select($sql, PagingInfo $pagingInfo=null)
  {
    if ($this->_conn == null) {
      $this->connect();
    }
    try {
      if ($pagingInfo != null && $pagingInfo->getPageSize() > 0) {
        // make a count query
        $countSql = preg_replace('/^\s*SELECT\s.*\s+FROM\s/Uis', 
                'SELECT COUNT(*) AS '.$this->quoteIdentifier('nRows').' FROM ', $sql);
        $result = $this->executeSql($countSql, true);
        $nRows = $result[0]['nRows'];
        // update pagingInfo
        $pagingInfo->setTotalCount($nRows);
        // set the limit on the query (NOTE: not supported by all databases)
        $limit = $pagingInfo->getPageSize();
        $offset = ($pagingInfo->getPage()-1)*$limit;
        $sql = preg_replace('/;$/', '', $sql);
        $sql .= ' LIMIT '.$limit;
        if ($offset > 0) {
          $sql .= ' OFFSET '.$offset;
        }
        $sql .= ';';
      }
      return $this->executeSql($sql, true);
    }
    catch (Exception $ex) {
      Log::error("The query: ".$sql."\ncaused the following exception:\n".$ex->getMessage(), __CLASS__);
      throw new PersistenceException("Error in persistent operation. See log file for details.");
    }
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
    if (isset($this->_relations['byrole'][$roleName])) {
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
  public function getAttributes(array $tags=array(), $matchMode='all')
  {
    $this->initAttributes();
    $result = array();
    if (sizeof($tags) == 0) {
      $result = array_values($this->_attributes['byname']);
    }
    else
    {
      foreach ($this->_attributes['byname'] as $name => $desc)
      {
        if ($desc->matchTags($tags, $matchMode)) {
          $result[] = $desc;
        }
      }
    }
    return $result;
  }
  /**
   * @see PersistenceMapper::getAttribute()
   */
  public function getAttribute($name)
  {
    $this->initAttributes();
    if (isset($this->_attributes['byname'][$name])) {
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
    $objects = $this->loadObjects($oid->getType(), $buildDepth, $criteria, null, $pagingInfo, $buildAttribs, $buildTypes);
    if (sizeof($objects) > 0)
      return $objects[0];
    else
      return null;
  }
  /**
   * @see PersistenceMapper::createImpl()
   * @note The type parameter is not used here because this class only constructs one type
   */
  protected function createImpl($type, $buildDepth=BUILDDEPTH_SINGLE, array $buildAttribs=array())
  {
    if ($buildDepth < 0 && !in_array($buildDepth, array(BUILDDEPTH_INFINITE, BUILDDEPTH_SINGLE, BUILDDEPTH_REQUIRED))) {
      throw new InvalidArgumentException("Build depth not supported: $buildDepth", __FILE__, __LINE__);
    }
    $persistenceFacade = PersistenceFacade::getInstance();

    // get attributes to load
    $attribs = array();
    if (sizeof($buildAttribs) > 0 && isset($buildAttribs[$this->getType()])) {
      $attribs = $buildAttribs[$this->getType()];
    }

    // create the object
    $object = $this->createObjectFromData($attribs);

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
      $relationDescs = $this->getRelations();

      // set dependend objects of this object
      foreach ($relationDescs as $curRelationDesc)
      {
        $t = $curRelationDesc->otherMinMultiplicity;
        $s = $curRelationDesc->otherAggregationKind;
        if ( ($buildDepth != BUILDDEPTH_SINGLE) && (($buildDepth > 0) || ($buildDepth == BUILDDEPTH_INFINITE) ||
          (($buildDepth == BUILDDEPTH_REQUIRED) && $curRelationDesc->otherMinMultiplicity > 0 && $curRelationDesc->otherAggregationKind != 'none')) )
        {
          if ($curRelationDesc instanceof RDBManyToManyRelationDescription) {
            $childObject = $persistenceFacade->create($curRelationDesc->otherType, BUILDDEPTH_SINGLE, $buildAttribs);
          }
          else {
            $childObject = $persistenceFacade->create($curRelationDesc->otherType, $newBuildDepth, $buildAttribs);
          }
          $object->setValue($curRelationDesc->otherRole, array($childObject));
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
    if ($this->_conn == null) {
      $this->connect();
    }
    // prepare object data
    // escape all values (except for primary key values)
    $appValues = array();
    if ($this->_dataConverter)
    {
      foreach ($object->getValueNames() as $valueName)
      {
        if (!$this->isPkValue($valueName))
        {
          $value = $object->getValue($valueName);
          $appValues[$valueName] = $value;
          $convertedValue = $this->_dataConverter->convertApplicationToStorage($value, $object->getValueProperty($valueName, 'type'), $valueName);
          $object->setValue($valueName, $convertedValue);
        }
      }
    }
    if ($object->getState() == STATE_NEW)
    {
      // insert new object
      $this->prepareInsert($object);
      $sqlArray = $this->getInsertSQL($object);
      foreach($sqlArray as $sqlStr) {
        $this->executeSql($sqlStr);
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
      foreach($sqlArray as $sqlStr) {
        $this->executeSql($sqlStr);
      }
    }

    // set converted values back to application values
    if ($this->_dataConverter)
    {
      foreach ($object->getValueNames() as $valueName)
      {
        if (!$this->isPkValue($valueName)) {
          $object->setValue($valueName, $appValues[$valueName], true);
        }
      }
    }
    $object->setState(STATE_CLEAN, false);

    // save many to many relations, if not already existing
    $relationDescs = $this->getRelations();
    $persistenceFacade = PersistenceFacade::getInstance();
    foreach ($relationDescs as $relationDesc)
    {
      if ($relationDesc instanceof RDBManyToManyRelationDescription)
      {
        $relatives = $object->getValue($relationDesc->otherRole);
        if (is_array($relatives))
        {
          foreach ($relatives as $relative)
          {
            if ($relative->getState() != STATE_NEW)
            {
              // check if the relation exists already
              $sqlStr = $this->getRelationObjectSelectSQL($object, $relative, $relationDesc);
              $rows = $this->executeSql($sqlStr, true);
              if (sizeof($rows) == 0)
              {
                $nmObj = $persistenceFacade->create($relationDesc->thisEndRelation->otherType);
                $nmObj->setValue($relationDesc->thisEndRelation->thisRole, array($object));
                $nmObj->setValue($relationDesc->otherEndRelation->otherRole, array($relative));
                $nmObj->save();
              }
            }
          }
        }
      }
    }

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
      $obj = $persistenceFacade->load($oid, BUILDDEPTH_SINGLE);
      if ($obj)
        $this->logAction($obj);
    }

    // delete object
    $sqlArray = $this->getDeleteSQL($oid);
    foreach($sqlArray as $sqlStr) {
      $this->executeSql($sqlStr);
    }
    // delete children
    if ($recursive)
    {
      // make sure that we only delete the composition children...
      $relDescs = $this->getRelations('child');
      foreach($relDescs as $relDesc)
      {
        $sql = $this->getRelationSelectSQL(new PersistentObjectProxy($oid), $relDesc, true);
        $childoids = $persistenceFacade->getOIDs($sql['type'], $sql['criteria']);
        foreach($childoids as $childoid) {
          $persistenceFacade->delete($childoid, $recursive);
        }
      }
      // ...for the others we have to break the foreign key relation
      $sqlArray = $this->getChildrenDisassociateSQL($oid, true);
      foreach($sqlArray as $sqlStr) {
        $this->executeSql($sqlStr);
      }
    }
    // postcondition: the object and all dependend objects are deleted from db
    return true;
  }
  /**
   * Get the database connection.
   * @return A reference to the PDOConnection object
   */
  function getConnection()
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
    return $this->loadObjectsImpl($type, $buildDepth, $criteria, $orderby, $pagingInfo, $buildAttribs, $buildTypes, true);
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
    if (sizeof($buildAttribs) > 0 && isset($buildAttribs[$this->getType()])) {
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
          $attribCondStr .= $name."=".$this->_conn->quote($value)." AND ";
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
    $sqlStr = $this->getSelectSQL($attribCondStr, null, $orderbyStr, $attribs);
    
    $data = $this->select($sqlStr, $pagingInfo);
    if (sizeof($data) == 0) {
      return $objects;
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
      // don't set the state recursive, because otherwise relations would be
      // initialized
      $object->setState(STATE_CLEAN, false);
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
  public function createObjectFromData(array $attribs=array(), array $data=array())
  {
    // determine if we are loading or creating
    $createFromLoadedData = (sizeof($data) > 0) ? true : false;

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
   * Apply the loaded object data to the object.
   * @note Subclasses must implement this method to define their object type.
   * @param object A reference to the object created with createObject method to which the data should be applied
   * @param objectData An associative array with the data returned by execution of the database select statement
   * 			(given by getSelectSQL).
   * @param attribs The build attributes for the type of object (given in the buildAttribs parameter of the loadImpl method)
   */
  protected function applyDataOnLoad(PersistentObject $object, array $objectData, array $attribs)
  {
    // set object data
    $values = array();
    $attributeDescriptions = $this->getAttributes();
    foreach($attributeDescriptions as $curAttributeDesc)
    {
      if (sizeof($attribs) == 0 || in_array($curAttributeDesc->name, $attribs))
      {
        $value = $objectData[$curAttributeDesc->name];
        if ($this->_dataConverter && !is_null($value)) {
          $value = $this->_dataConverter->convertStorageToApplication($value, $curAttributeDesc->type, $curAttributeDesc->name);
        }
        $values[$curAttributeDesc->name] = $value;
      }
    }
    $object->initialize($values);
  }
  /**
   * Apply the default data to the object.
   * @note Subclasses must implement this method to define their object type.
   * @param object A reference to the object created with createObject method to which the data should be applied
   * @param attribs The build attributes for the type of object (given in the buildAttribs parameter of the loadImpl method).
   */
  protected function applyDataOnCreate(PersistentObject $object, array $attribs)
  {
    // set object data
    $values = array();
    $attributeDescriptions = $this->getAttributes();
    foreach($attributeDescriptions as $curAttributeDesc)
    {
      if (sizeof($attribs) == 0 || in_array($curAttributeDesc->name, $attribs))
      {
        // don't override dummy ids
        if (!$this->isPkValue($curAttributeDesc->name))
        {
          $value = $curAttributeDesc->defaultValue;
          if ($this->_dataConverter) {
            $value = $this->_dataConverter->convertStorageToApplication($value, $curAttributeDesc->type, $curAttributeDesc->name);
          }
          $values[$curAttributeDesc->name] = $value;
        }
      }
    }
    $object->initialize($values);
  }
  /**
   * Append the child data to an object. If the buildDepth does not determine to load a
   * child generation, only the oids of the children will be loaded.
   * @param object A reference to the object to append the children to
   * @param buildDepth @see PersistenceFacade::loadObjects()
   * @param buildAttribs @see PersistenceFacade::loadObjects()
   * @param buildTypes @see PersistenceFacade::loadObjects()
   */
  public function appendRelationData(PersistentObject $object, $buildDepth, array $buildAttribs=array(), array $buildTypes=array())
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
    $relDescs = $this->getRelations();
    foreach($relDescs as $relDesc)
    {
      $sql = $this->getRelationSelectSQL(new PersistentObjectProxy($object->getOID(), $object), $relDesc);
      $role = $sql['role'];
      $relatives = array();

      // if the build depth is not satisfied already we load the complete objects and add them
      if ($loadNextGeneration)
      {
        if ($relDesc instanceof RDBManyToManyRelationDescription)
        {
          // if the relation is a many to many relation, we have to load
          // the relation proxies and add the real subjects
          $nmRelativeProxies = $this->loadManyToManyProxies($sql['type'], 
                  $sql['criteria'], $relDesc->otherEndRelation->otherRole);
          foreach ($nmRelativeProxies as $nmRelativeProxy) {
            $relatives[] = $nmRelativeProxy->getRealSubject();
          }
        }
        else
        {
          $tmp = null;
          if ($relationDescription->isMultiValued()) {
            $relatives = $persistenceFacade->loadObjects($sql['type'], $newBuildDepth, 
                    $sql['criteria'], null, $tmp, $buildAttribs, $buildTypes);          
          }
          else {
            $relatives = $persistenceFacade->loadFirstObject($sql['type'], $newBuildDepth, 
                    $sql['criteria'], null, $tmp, $buildAttribs, $buildTypes);                      
          }
        }
      }
      // otherwise set the value to not initialized.
      // the Node will initialize it with the proxies for the relation objects
      // on first access
      else {
        $relatives = null;
        if ($object instanceof Node) {
          $object->setRelationState($role, Node::RELATION_STATE_UNINITIALIZED);
        }
      }
      // set the value
      $object->setValue($role, $relatives);
    }
  }
  /**
   * Load the PersistentObjectProxy instances for the other end of a many
   * to many relation
   * @param nmType The type of object representing the many to many relation
   * @param criteria The were clause used to select the many to many instances
   * @param otherEndRole The role of the PersistentObjectProxy instances in the relation
   * @return array 
   */
  protected function loadManyToManyProxies($nmType, $criteria, $otherEndRole)
  {
    $persistenceFacade = PersistenceFacade::getInstance();
    $nmRelativeProxies = array();
    $nmObjects = $persistenceFacade->loadObjects($nmType, BUILDDEPTH_SINGLE, $criteria);
    for ($i=0, $countI=sizeof($nmObjects); $i<$countI; $i++)
    {
      // we expect only one object at the other end of each nm instance
      $otherEndObj = $nmObjects[$i]->getValue($otherEndRole);
      if (!$otherEndObj instanceof PersistentObjectProxy) {
        throw new ErrorException("PersistentObjectProxy instance expected");
      }
      $nmRelativeProxies[] = $otherEndObj;
    }
    return $nmRelativeProxies;
  }
  /**
   * Load the PersistentObjectProxy instances for objects in the given relation
   * and set the appropriate value in the given object
   * @param object The object that has the relation
   * @param relationDescription The RelationDescription describing the relation
   */
  public function initializeRelation(PersistentObject $object, $relationDescription)
  {
    $relatives = array();
    $sql = $this->getRelationSelectSQL(new PersistentObjectProxy($object->getOID(), $object), $relationDescription);
    $role = $sql['role'];
    if ($relationDescription instanceof RDBManyToManyRelationDescription)
    {
      // if the relation is a many to many relation, we have to load
      // the relation proxies and add them
      $nmRelativeProxies = $this->loadManyToManyProxies($sql['type'], 
              $sql['criteria'], $relationDescription->otherEndRelation->otherRole);
      foreach ($nmRelativeProxies as $nmRelativeProxy) {
        $relatives[] = $nmRelativeProxy;
      }
    }
    else
    {
      $persistenceFacade = PersistenceFacade::getInstance();
      if ($relationDescription->isMultiValued())
      {
        $oids = $persistenceFacade->getOIDs($sql['type'], $sql['criteria']);
        foreach ($oids as $oid) {
          $relatives[] = new PersistentObjectProxy($oid);
        }
      }
      else {
        $oid = $persistenceFacade->getFirstOID($sql['type'], $sql['criteria']);
        if ($oid != null) {
          $relatives = new PersistentObjectProxy($oid);
        }
        else {
          $relatives = null;
        }
      }
    }
    // set the value
    $object->setValue($role, $relatives);
    $object->setRelationState($role, Node::RELATION_STATE_INITIALIZED);
  }
  /**
   * @see PersistenceMapper::startTransaction()
   */
  public function startTransaction()
  {
    if ($this->_conn == null) {
      $this->connect();
    }
    $this->_conn->beginTransaction();
  }
  /**
   * @see PersistenceMapper::commitTransaction()
   */
  public function commitTransaction()
  {
    if ($this->_conn == null) {
      $this->connect();
    }
    $this->_conn->commit();
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
    $this->_conn->rollBack();
  }

  /**
   * TEMPLATE METHODS
   * Subclasses must implement this method to define their object type.
   */

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
   * Factory method for the supported object type.
   * @note Subclasses must implement this method to define their object type.
   * @param oid The object id (maybe null)
   * @return A reference to the created object.
   */
  abstract protected function createObject(ObjectId $oid=null);
  /**
   * Set the object primary key values for inserting the object to the database.
   * @note Subclasses must implement this method to define their object type.
   * @param object A reference to the object to insert.
   * @note The object does not have the final object id set. If a new id value for a primary key column is needed
   * for the insert statement, use RDBMapper::getNextId().
   */
  abstract protected function prepareInsert(PersistentObject $object);
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
   * @param object The object to load the relatives for. Maybe an proxy instance only.
   * @param relationDescription The RelationDescription describing the relative type
   * @param compositionOnly True/False indicates wether to only select composition relatives or all [default: false].
   * @return An associative array with the keys 'role', 'type' and 'criteria', where 'role' is the relative's role,
   *      'type' is the relative's type and 'criteria' is the corresponding SQL condition to be used as criteria 
   *      parameter in PersistenceFacade::loadObjects().
   */
  abstract protected function getRelationSelectSQL(PersistentObjectProxy $object, $relationDescription, $compositionOnly=false);
  /**
   * Get the SQL command to select the relation object in a many to many relation from the database.
   * @note Subclasses must implement this method to define their object type.
   * @param object The object at this end of the relation.
   * @param relative The object at the other end of the relation.
   * @param relationDesc The RDBManyToManyRelationDescription instance describing the relation.
   * @return A SQL command that selects all relation objects connecting the two objects in a way
   * 		defined in the relation description.
   */
  abstract protected function getRelationObjectSelectSQL(PersistentObject $object, PersistentObject $relative, RDBManyToManyRelationDescription $relationDesc);
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
  /**
   * Get the name of the database table, where this type is mapped to
   * @return The name of the table
   */
  abstract protected function getTableName();  
}
?>
