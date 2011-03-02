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
require_once(WCMF_BASE."wcmf/lib/persistence/class.InsertOperation.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.UpdateOperation.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.DeleteOperation.php");
require_once(WCMF_BASE."wcmf/lib/persistence/converter/class.DataConverter.php");
require_once(WCMF_BASE."wcmf/lib/util/class.InifileParser.php");


$includePath = get_include_path();
if (strpos($includePath, 'Zend') === false) {
  set_include_path(get_include_path().PATH_SEPARATOR.WCMF_BASE.'wcmf/3rdparty/zend');
}
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
  private static $connections = array();

  private $_connParams = null; // database connection parameters
  private $_conn = null;       // database connection
  private $_dbPrefix = '';     // database prefix (if given in the configuration file)

  private $_relations = null;
  private $_attributes = null;

  // prepared statements
  private $_idSelectStmt = null;
  private $_idInsertStmt = null;
  private $_idUpdateStmt = null;

  private $_dataConverter = null;

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
      $connectionKey = join(',', array($this->_connParams['dbType'], $this->_connParams['dbHostName'],
          $this->_connParams['dbUserName'], $this->_connParams['dbPassword'], $this->_connParams['dbName']));

      // reuse an existing connection if possible
      if (isset(self::$connections[$connectionKey])) {
        $this->_conn = self::$connections[$connectionKey];
      }
      else
      {
        try {
          // create new connection
          $pdoParams = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
          );
          if ($this->_connParams['dbType'] == 'mysql') {
            $pdoParams[PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = true;

          }
          $params = array(
            'host' => $this->_connParams['dbHostName'],
            'username' => $this->_connParams['dbUserName'],
            'password' => $this->_connParams['dbPassword'],
            'dbname' => $this->_connParams['dbName'],
            'driver_options' => $pdoParams,
            'profiler' => false
          );
          if (!empty($this->_connParams['dbPort'])) {
            $params['port'] = $this->_connParams['dbPort'];
          }
          $this->_conn = Zend_Db::factory('Pdo_'.ucfirst($this->_connParams['dbType']), $params);
          $this->_conn->setFetchMode(Zend_Db::FETCH_ASSOC);

          // store the connection for reuse
          self::$connections[$connectionKey] = $this->_conn;
        }
        catch(Exception $ex) {
          throw new PersistenceException("Connection to ".$this->_connParams['dbHostName'].".".
            $this->_connParams['dbName']." failed: ".$ex->getMessage());
        }
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
   * Enable profiling
   */
  public function enableProfiler()
  {
    if ($this->_conn == null) {
      $this->connect();
    }
    $this->_conn->getProfiler()->setEnabled(true);
  }
  /**
   * Disable profiling
   */
  public function disableProfiler()
  {
    if ($this->_conn == null) {
      $this->connect();
    }
    $this->_conn->getProfiler()->setEnabled(false);
  }
  /**
   * Get the profiler
   * @return Zend_Db_Profiler
   */
  public function getProfiler()
  {
    if ($this->_conn == null) {
      $this->connect();
    }
    return $this->_conn->getProfiler();
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
   * @see PersistenceMapper::quoteIdentifier
   */
  public function quoteIdentifier($identifier)
  {
    if ($this->_conn == null) {
      $this->connect();
    }
    return $this->_conn->quoteIdentifier($identifier);
  }
  /**
   * @see PersistenceMapper::quoteValue
   */
  public function quoteValue($value)
  {
    if ($this->_conn == null) {
      $this->connect();
    }
    return $this->_conn->quote($value);
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
   * @param sql The SQL statement as string
   * @param isSelect True/False wether the statement is a select statement, optional [default: false]
   * @param bindValues An array of data to bind to the placeholders, optional [default: empty array]
   * @return If isSelect is true, an array as the result of PDOStatement::fetchAll(PDO::FETCH_ASSOC),
   * the number of affected rows else
   */
  public function executeSql($sql, $isSelect=false, $bindValues=array())
  {
    if ($this->_conn == null) {
      $this->connect();
    }
    try {
      if ($isSelect) {
        $stmt = $this->_conn->prepare($sql);
        $stmt->execute($bindValues);
        $result = $stmt->fetchAll();
        $stmt->closeCursor();
        return $result;
      }
      else {
        // maybe use insert, update, delete methods from Zend_Db_Adapter
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
   * @param selectStmt A Zend_Db_Select instance
   * @param pagingInfo An PagingInfo instance describing which page to load, optional [default: null]
   * @return An array as the result of PDOStatement::fetchAll(PDO::FETCH_ASSOC)
   */
  protected function select(Zend_Db_Select $selectStmt, PagingInfo $pagingInfo=null)
  {
    if ($this->_conn == null) {
      $this->connect();
    }
    try {
      if ($pagingInfo != null && $pagingInfo->getPageSize() > 0) {
        if (!$pagingInfo->isIgnoringTotalCount())
        {
          // make a count query
          $columnPart = $selectStmt->getPart(Zend_Db_Select::COLUMNS);
          $selectStmt->reset(Zend_Db_Select::COLUMNS);
          $selectStmt->columns(array('nRows' => new Zend_Db_Expr('COUNT(*)')));
          $result = $selectStmt->query();
          $row = $result->fetchRow();
          $nRows = $row['nRows'];
          // update pagingInfo
          $pagingInfo->setTotalCount($nRows);
          // reset the query
          $selectStmt->reset(Zend_Db_Select::COLUMNS);
          foreach ($columnPart as $columnDef) {
            $selectStmt->columns(array($columnDef[2] => $columnDef[1]), $columnDef[0]);
          }
        }
        // set the limit on the query (NOTE: not supported by all databases)
        $limit = $pagingInfo->getPageSize();
        $offset = $pagingInfo->getOffset();
        $selectStmt->limit($limit, $offset);
      }
      $result = $selectStmt->query();
      return $result->fetchAll();
    }
    catch (Exception $ex) {
      Log::error("The query: ".$selectStmt."\ncaused the following exception:\n".$ex->getMessage(), __CLASS__);
      throw new PersistenceException("Error in persistent operation. See log file for details.");
    }
  }
  /**
   * @see PersistenceMapper::executeOperation()
   */
  public function executeOperation(PersistenceOperation $operation)
  {
    if ($operation->getType() != $this->getType()) {
      throw new IllegalArgumentException("Operation: ".$operation.
              " can't be executed by ".get_class($this));
    }
    if ($this->_conn == null) {
      $this->connect();
    }

    // transform table name
    $tableName = $this->getRealTableName();

    // translate value names to columns
    $translatedValues = array();
    foreach($operation->getValues() as $name => $value) {
      $attrDesc = $this->getAttribute($name);
      if ($attrDesc) {
        $translatedValues[$attrDesc->getColumn()] = $value;
      }
    }

    // transform criteria
    $where = array();
    foreach ($operation->getCriteria() as $curCriteria) {
      $condition = $this->renderCriteria($curCriteria, true, $tableName);
      $where[$condition] = $curCriteria->getValue();
    }

    // execute the statement
    $affectedRows = 0;
    try {
      if ($operation instanceof InsertOperation) {
        $affectedRows = $this->_conn->insert($tableName, $translatedValues);
      }
      elseif ($operation instanceof UpdateOperation) {
        $affectedRows = $this->_conn->update($tableName, $translatedValues, $where);
      }
      elseif ($operation instanceof DeleteOperation) {
        $affectedRows = $this->_conn->delete($tableName, $where);
      }
      else {
        throw new IllegalArgumentException("Unsupported Operation: ".$operation);
      }
    }
    catch (Exception $ex) {
      Log::error("The operation: ".$operation."\ncaused the following exception:\n".$ex->getMessage(), __CLASS__);
      throw new PersistenceException("Error in persistent operation. See log file for details.");
    }
    return $affectedRows;
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
    if ($this->_relations == null)
    {
      $this->_relations = array();
      $this->_relations['byrole'] = $this->getRelationDescriptions();
      $this->_relations['parent'] = array();
      $this->_relations['child'] = array();
      $this->_relations['undefined'] = array();
      foreach ($this->_relations['byrole'] as $role => $desc)
      {
        $hierarchyType = $desc->getHierarchyType();
        if ($hierarchyType == 'parent') {
          $this->_relations['parent'][] = $desc;
        }
        elseif ($hierarchyType == 'child') {
          $this->_relations['child'][] = $desc;
        }
        else {
          $this->_relations['undefined'][] = $desc;
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
  public function isPkValue($name)
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
  protected function constructOID($data)
  {
    $pkNames = $this->getPkNames();
    $ids = array();
    foreach ($pkNames as $pkName) {
      array_push($ids, $data[$pkName]);
    }
    return new ObjectId($this->getType(), $ids);

  }
  /**
   * Render a Criteria instance as string.
   * @param criteria The Criteria instance
   * @param usePlaceholder True/False wether to use a placeholder ('?') instead of the value, optional [default: false]
   * @param tableName The table name to use (may differ from criteria's type attribute), optional
   * @param columnName The column name to use (may differ from criteria's attribute attribute), optional
   * @return String
   */
  public function renderCriteria(Criteria $criteria, $usePlaceholder=falses, $tableName=null, $columnName=null)
  {
    if ($tableName === null) {
      $tableName = $criteria->getType();
    }
    if ($columnName === null) {
      $columnName = $criteria->getAttribute();
    }
    $result = $this->quoteIdentifier($tableName).".".$this->quoteIdentifier($criteria->getAttribute()).
                " ".$criteria->getOperator()." ";
    $value = $criteria->getValue();
    $valueStr = '?';
    if (!$usePlaceholder) {
      $valueStr = $this->quoteValue($value);
    }
    if (is_array($value)) {
      $result .= "(".$valueStr.")";
    }
    else {
      $result .= $valueStr;
    }
    return $result;
  }
  /**
   * @see PersistenceMapper::loadImpl()
   */
  protected function loadImpl(ObjectId $oid, $buildDepth=BUILDDEPTH_SINGLE, $buildAttribs=null, $buildTypes=null)
  {
    // delegate to loadObjects
    $criteria = $this->createPKCondition($oid);
    $pagingInfo = new PagingInfo(1, true);
    $objects = $this->loadObjects($oid->getType(), $buildDepth, $criteria, null, $pagingInfo, $buildAttribs, $buildTypes);
    if (sizeof($objects) > 0) {
      return $objects[0];
    }
    else {
      return null;
    }
  }
  /**
   * @see PersistenceMapper::createImpl()
   * @note The type parameter is not used here because this class only constructs one type
   */
  protected function createImpl($type, $buildDepth=BUILDDEPTH_SINGLE, $buildAttribs=null)
  {
    if ($buildDepth < 0 && !in_array($buildDepth, array(BUILDDEPTH_INFINITE, BUILDDEPTH_SINGLE, BUILDDEPTH_REQUIRED))) {
      throw new InvalidArgumentException("Build depth not supported: $buildDepth", __FILE__, __LINE__);
    }
    $persistenceFacade = PersistenceFacade::getInstance();

    // get attributes to load
    $attribs = null;
    if ($buildAttribs !== null && isset($buildAttribs[$this->getType()])) {
      $attribs = $buildAttribs[$this->getType()];
    }

    // create the object
    $object = $this->createObjectFromData(array(), $attribs);

    // recalculate build depth for the next generation
    $newBuildDepth = $buildDepth;
    if ($buildDepth != BUILDDEPTH_REQUIRED && $buildDepth != BUILDDEPTH_SINGLE && $buildDepth != BUILDDEPTH_INFINITE && $buildDepth > 0) {
      $newBuildDepth = $buildDepth-1;
    }

    // prevent infinite recursion
    if ($buildDepth < BUILDDEPTH_MAX)
    {
      $relationDescs = $this->getRelations();

      // set dependend objects of this object
      foreach ($relationDescs as $curRelationDesc)
      {
        if ( ($buildDepth != BUILDDEPTH_SINGLE) && (($buildDepth > 0) || ($buildDepth == BUILDDEPTH_INFINITE) ||
          (($buildDepth == BUILDDEPTH_REQUIRED) && $curRelationDesc->getOtherMinMultiplicity() > 0 && $curRelationDesc->getOtherAggregationKind() != 'none')) )
        {
          $childObject = null;
          if ($curRelationDesc instanceof RDBManyToManyRelationDescription) {
            $childObject = $persistenceFacade->create($curRelationDesc->getOtherType(), BUILDDEPTH_SINGLE, $buildAttribs);
          }
          else {
            $childObject = $persistenceFacade->create($curRelationDesc->getOtherType(), $newBuildDepth, $buildAttribs);
          }
          $object->setValue($curRelationDesc->getOtherRole(), array($childObject));
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
    $persistenceFacade = PersistenceFacade::getInstance();
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

    // set all missing attributes
    $this->prepareForStorage($object);

    if ($object->getState() == PersistentObject::STATE_NEW)
    {
      // insert new object
      $operations = $this->getInsertSQL($object);
      foreach($operations as $operation) {
        $mapper = $persistenceFacade->getMapper($operation->getType());
        $mapper->executeOperation($operation);
      }
      // log action
      $this->logAction($object);
    }
    else if ($object->getState() == PersistentObject::STATE_DIRTY)
    {
      // save existing object
      // precondition: the object exists in the database

      // log action
      $this->logAction($object);

      // save object
      $operations = $this->getUpdateSQL($object);
      foreach($operations as $operation) {
        $mapper = $persistenceFacade->getMapper($operation->getType());
        $mapper->executeOperation($operation);
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
    $object->setState(PersistentObject::STATE_CLEAN, false);

    // postcondition: the object is saved to the db
    //                the object state is STATE_CLEAN
    //                attributes are only inserted if their values differ from ''
    return true;
  }
  /**
   * @see PersistenceMapper::deleteImpl()
   */
  protected function deleteImpl(ObjectId $oid)
  {
    $persistenceFacade = PersistenceFacade::getInstance();
    if ($this->_conn == null) {
      $this->connect();
    }

    // log action
    if ($this->isLogging())
    {
      $obj = $persistenceFacade->load($oid, BUILDDEPTH_SINGLE);
      if ($obj) {
        $this->logAction($obj);
      }
    }

    // delete object
    $affectedRows = 0;
    $operations = $this->getDeleteSQL($oid);
    foreach($operations as $operation) {
      $mapper = $persistenceFacade->getMapper($operation->getType());
      $affectedRows += $mapper->executeOperation($operation);
    }
    // only delete children if the object was deleted
    if ($affectedRows > 0)
    {
      $proxy = new PersistentObjectProxy($oid);
      $relationDescs = $this->getRelations('child');
      foreach($relationDescs as $relationDesc)
      {
        $isManyToMany = ($relationDesc instanceof RDBManyToManyRelationDescription);
        $isComposite = ($relationDesc->getThisAggregationKind() == 'composite' ||
                $isManyToMany);
        if ($isManyToMany) {
          // in a many to many relation we only use the relation description
          // that points to relation objects
          $relationDesc = $relationDesc->getThisEndRelation();
        }

        // load related objects
        $otherType = $relationDesc->getOtherType();
        $otherMapper = $persistenceFacade->getMapper($otherType);
        $objects = $otherMapper->loadRelatedObjects($proxy, $relationDesc->getThisRole(),
                BUILDDEPTH_SINGLE);
        foreach($objects as $object)
        {
          if ($isComposite) {
            // delete composite and relation object children
            $object->delete();
          }
          else
          {
            // unlink shared children
            $object->setValue($relationDesc->getThisRole(), null);
            $object->save();
          }
        }
      }
    }
    // postcondition: the object and all dependend objects are deleted from db
    return true;
  }
  /**
   * Get the database connection.
   * @return A reference to the PDOConnection object
   */
  public function getConnection()
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
    $objects = $this->loadObjectsFromQueryParts($type, BUILDDEPTH_SINGLE, $criteria, $orderby,
            $pagingInfo, array($type => array()), array($type));

    // collect oids
    for ($i=0; $i<sizeof($objects); $i++) {
      $oids[] = $objects[$i]->getOID();
    }
    return $oids;
  }
  /**
   * @see PersistenceFacade::loadObjects()
   */
  public function loadObjects($type, $buildDepth=BUILDDEPTH_SINGLE, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null,
    $buildAttribs=null, $buildTypes=null)
  {
    $objects = $this->loadObjectsFromQueryParts($type, $buildDepth, $criteria, $orderby,
            $pagingInfo, $buildAttribs, $buildTypes);
    return $objects;
  }
  /**
   * @see PersistenceMapper::loadRelatedObjects()
   */
  public function loadRelatedObjects(PersistentObjectProxy $otherObjectProxy, $otherRole, $buildDepth=BUILDDEPTH_SINGLE,
    $buildAttribs=null, $buildTypes=null)
  {
    if ($buildDepth < 0 && !in_array($buildDepth, array(BUILDDEPTH_INFINITE, BUILDDEPTH_SINGLE))) {
      throw new IllegalArgumentException("Build depth not supported: $buildDepth", __FILE__, __LINE__);
    }
    $objects = array();
    $type = $this->getType();

    // check buildTypes
    if ($buildTypes !== null && !in_array($type, $buildTypes)) {
      return $objects;
    }
    // get attributes to load
    $attribs = null;
    if ($buildAttribs !== null && isset($buildAttribs[$type])) {
      $attribs = $buildAttribs[$type];
    }

    // create query
    $selectStmt = $this->getRelationSelectSQL($otherObjectProxy, $otherRole, $attribs);

    $pagingInfo = null;
    $objects = $this->loadObjectsFromSQL($selectStmt, $buildDepth, $pagingInfo, $buildAttribs, $buildTypes);
    return $objects;
  }
  /**
   * Load objects defined by several query parts.
   * @param type The type of the object
   * @param buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (except BUILDDEPTH_REQUIRED, BUILDDEPTH_PROXIES_ONLY) [default: BUILDDEPTH_SINGLE]
   * @param criteria An array of Criteria instances that define conditions on the type's attributes (maybe null). [default: null]
   * @param orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (maybe null). [default: null]
   * @param joins An array holding join conditions if other tables are involved in this query (maybe null). [default: null]
   * @param pagingInfo A reference PagingInfo instance (maybe null). [default: null]
   * @param buildAttribs An assoziative array listing the attributes to load (default: null loads all attributes)
   *        (keys: the types, values: an array of attributes of the type to load)
   *        Use this to load only a subset of attributes
   * @param buildTypes An array listing the (sub-)types to include
   */
  protected function loadObjectsFromQueryParts($type, $buildDepth=BUILDDEPTH_SINGLE, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null,
    $buildAttribs=null, $buildTypes=null)
  {
    if ($buildDepth < 0 && !in_array($buildDepth, array(BUILDDEPTH_INFINITE, BUILDDEPTH_SINGLE))) {
      throw new IllegalArgumentException("Build depth not supported: $buildDepth", __FILE__, __LINE__);
    }
    $objects = array();

    // check buildTypes
    if ($buildTypes !== null && !in_array($type, $buildTypes)) {
      return $objects;
    }
    // get attributes to load
    $attribs = null;
    if ($buildAttribs !== null && isset($buildAttribs[$type])) {
      $attribs = $buildAttribs[$type];
    }

    // create query
    $selectStmt = $this->getSelectSQL($criteria, null, $orderby, $attribs);

    $objects = $this->loadObjectsFromSQL($selectStmt, $buildDepth, $pagingInfo, $buildAttribs, $buildTypes);
    return $objects;
  }
  /**
   * Load objects defined by a select statement.
   * @param selectStmt A Zend_Db_Select instance
   * @param buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (except BUILDDEPTH_REQUIRED, BUILDDEPTH_PROXIES_ONLY) [default: BUILDDEPTH_SINGLE]
   * @param criteria An array of Criteria instances that define conditions on the type's attributes (maybe null). [default: null]
   * @param orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (maybe null). [default: null]
   * @param joins An array holding join conditions if other tables are involved in this query (maybe null). [default: null]
   * @param pagingInfo A reference PagingInfo instance (maybe null). [default: null]
   * @param buildAttribs An assoziative array listing the attributes to load (default: null loads all attributes)
   *        (keys: the types, values: an array of attributes of the type to load)
   *        Use this to load only a subset of attributes
   * @param buildTypes An array listing the (sub-)types to include
   * @return An array of PersistentObject instances
   */
  public function loadObjectsFromSQL(Zend_Db_Select $selectStmt, $buildDepth=BUILDDEPTH_SINGLE, PagingInfo $pagingInfo=null,
    $buildAttribs=null, $buildTypes=null)
  {
    if ($this->_conn == null) {
      $this->connect();
    }
    $objects = array();

    $data = $this->select($selectStmt, $pagingInfo);
    if (sizeof($data) == 0) {
      return $objects;
    }

    $numObjects = sizeof($data);
    for ($i=0; $i<$numObjects; $i++)
    {
      // create the object
      // (since we only loaded the requested attributes, we can set the attribs parameter null safely)
      $object = $this->createObjectFromData($data[$i], null);

      // add related objects
      $this->addRelatedObjects($object, $buildDepth, $buildAttribs, $buildTypes);

      // don't set the state recursive, because otherwise relations would be
      // initialized
      $object->setState(PersistentObject::STATE_CLEAN, false);
      $objects[] = $object;
    }
    return $objects;
  }
  /**
   * Create an object of the mapper's type with the given attributes from the given data
   * @param data An associative array with the attribute names as keys and the attribute values as values
   * @param attribs An array of attributes to add (default: null add all attributes)
   * @return A reference to the object
   */
  protected function createObjectFromData(array $data, $attribs=null)
  {
    // determine if we are loading or creating
    $createFromLoadedData = (sizeof($data) > 0) ? true : false;

    // initialize data and oid
    $oid = null;
    if ($createFromLoadedData) {
      $oid = $this->constructOID($data);
    }

    // construct object
    $object = $this->createObject($oid);

    // apply data to the created object
    if ($createFromLoadedData) {
      $this->applyDataOnLoad($object, $data, $attribs);
    }
    else {
      $this->applyDataOnCreate($object, $attribs);
    }
    return $object;
  }
  /**
   * Apply the loaded object data to the object.
   * @note Subclasses must implement this method to define their object type.
   * @param object A reference to the object created with createObject method to which the data should be applied
   * @param objectData An associative array with the data returned by execution of the database select statement
   * 			(given by getSelectSQL).
   * @param attribs An array of attributes to add (default: null add all attributes)
   */
  protected function applyDataOnLoad(PersistentObject $object, array $objectData, $attribs=null)
  {
    // set object data
    $values = array();
    foreach($objectData as $name => $value)
    {
      if ($attribs == null || in_array($name, $attribs))
      {
        $curAttributeDesc = $this->getAttribute($name);
        if ($this->_dataConverter && !is_null($value)) {
          $value = $this->_dataConverter->convertStorageToApplication($value, $curAttributeDesc->getType(), $name);
        }
        $values[$name] = $value;
      }
    }
    $object->initialize($values);
  }
  /**
   * Apply the default data to the object.
   * @note Subclasses must implement this method to define their object type.
   * @param object A reference to the object created with createObject method to which the data should be applied
   * @param attribs An array of attributes to add (default: null add all attributes)
   */
  protected function applyDataOnCreate(PersistentObject $object, $attribs=null)
  {
    // set object data
    $values = array();
    $attributeDescriptions = $this->getAttributes();
    foreach($attributeDescriptions as $curAttributeDesc)
    {
      $name = $curAttributeDesc->getName();
      if ($attribs == null || in_array($name, $attribs))
      {
        $value = $curAttributeDesc->getDefaultValue();
        if ($this->_dataConverter && !is_null($value)) {
          $value = $this->_dataConverter->convertStorageToApplication($value, $curAttributeDesc->getType(), $name);
        }
        $values[$name] = $value;
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
  protected function addRelatedObjects(PersistentObject $object, $buildDepth=BUILDDEPTH_SINGLE, $buildAttribs=null, $buildTypes=null)
  {
    $persistenceFacade = PersistenceFacade::getInstance();

    // recalculate build depth for the next generation
    $newBuildDepth = $buildDepth;
    if ($buildDepth != BUILDDEPTH_SINGLE && $buildDepth != BUILDDEPTH_INFINITE && $buildDepth > 0) {
      $newBuildDepth = $buildDepth-1;
    }
    $loadNextGeneration = (($buildDepth != BUILDDEPTH_SINGLE) && ($buildDepth > 0 || $buildDepth == BUILDDEPTH_INFINITE));

    // get dependend objects of this object
    $relationDescs = $this->getRelations();
    foreach($relationDescs as $relationDesc)
    {
      $role = $relationDesc->getOtherRole();

      // if the build depth is not satisfied already we load the complete objects and add them
      if ($loadNextGeneration)
      {
        $relatives = array();
        $relatives = $this->loadRelation($object, $role, $newBuildDepth, $buildAttribs, $buildTypes);
        // set the value
        $object->setValue($role, $relatives);
      }
      // otherwise set the value to not initialized.
      // the Node will initialize it with the proxies for the relation objects
      // on first access
      else
      {
        if ($object instanceof Node) {
          $object->addRelation($role);
        }
      }
    }
  }
  /**
   * @see PersistenceMapper::loadRelation()
   */
  public function loadRelation(PersistentObject $object, $role, $buildDepth=BUILDDEPTH_SINGLE,
    $buildAttribs=null, $buildTypes=null)
  {
    $persistenceFacade = PersistenceFacade::getInstance();
    $relationDescription = $this->getRelation($role);
    $otherType = $relationDescription->getOtherType();
    $otherMapper = $persistenceFacade->getMapper($otherType);
    $relatives = array();

    // for proxies only load the oid information
    if ($buildDepth == BUILDDEPTH_PROXIES_ONLY)
    {
      $relatedObjects = $otherMapper->loadRelatedObjects(PersistentObjectProxy::fromObject($object),
              $relationDescription->getThisRole(), BUILDDEPTH_SINGLE, array($otherType => array()));
      if ($relationDescription->isMultiValued())
      {
        foreach ($relatedObjects as $relatedObject) {
          $relatives[] = new PersistentObjectProxy($relatedObject->getOID());
        }
      }
      else
      {
        if (sizeof($relatedObjects) > 0) {
          $relatives = new PersistentObjectProxy($relatedObjects[0]->getOID());
        }
        else {
          $relatives = null;
        }
      }
    }
    // load the complete objects in all other cases
    else
    {
      $relatives = $otherMapper->loadRelatedObjects(PersistentObjectProxy::fromObject($object),
              $relationDescription->getThisRole(), $buildDepth, $buildAttribs, $buildTypes);
      if (!$relationDescription->isMultiValued() && sizeof($relatives) > 0) {
        $relatives = $relatives[0];
      }
    }

    return $relatives;
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
   * Set the object primary key and foreign key values for storing the object in the database.
   * @note Subclasses must implement this method to define their object type.
   * @param object A reference to the object to insert.
   * @note The object does not have the final object id set. If a new id value for a primary key column is needed
   * for the insert statement, use RDBMapper::getNextId().
   */
  abstract protected function prepareForStorage(PersistentObject $object);
  /**
   * Get the SQL command to select object data from the database.
   * @note Subclasses must implement this method to define their object type.
   * @param criteria An array of Criteria instances that define conditions on the type's attributes (maybe null). [default: null]
   * @param alias The alias for the table name [default: null uses none].
   * @param orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (maybe null). [default: null]
   * @param attribs An array listing the attributes to load [default: null loads all attributes].
   * @return Zend_Db_Select instance that selects all object data that match the condition or an array with the query parts.
   * @note The names of the data item columns MUST match the data item names provided in the '_datadef' array from RDBMapper::getObjectDefinition()
   *       Use alias names if not! The selected data will be put into the '_data' array of the object definition.
   */
  abstract public function getSelectSQL($criteria=null, $alias=null, $orderby=null, $attribs=null);
  /**
   * Get the SQL command to select those objects from the database that are related to the given object.
   * @note Subclasses must implement this method to define their object type.
   * @param otherObjectProxy A PersistentObjectProxy for the object to load the relatives for.
   * @param otherRole The role of the other object in relation to the objects to load.
   * @param attribs An array listing the attributes to load [default: null loads all attributes].
   * @return Zend_Db_Select instance
   */
  abstract protected function getRelationSelectSQL(PersistentObjectProxy $otherObjectProxy, $otherRole, $attribs=null);
  /**
   * Get the SQL command to insert a object into the database.
   * @note Subclasses must implement this method to define their object type.
   * @param object A reference to the object to insert.
   * @return Array of PersistenceOperation instances that insert a new object.
   */
  abstract protected function getInsertSQL(PersistentObject $object);
  /**
   * Get the SQL command to update a object in the database.
   * @note Subclasses must implement this method to define their object type.
   * @param object A reference to the object to update.
   * @return Array of PersistenceOperation instances that update an existing object.
   */
  abstract protected function getUpdateSQL(PersistentObject $object);
  /**
   * Get the SQL command to delete a object from the database.
   * @note Subclasses must implement this method to define their object type.
   * @param oid The object id of the object to delete.
   * @return Array of PersistenceOperation instances that delete an existing object.
   */
  abstract protected function getDeleteSQL(ObjectId $oid);
  /**
   * Create an array of condition Criteria instances for the primary key values
   * @note Subclasses must implement this method to define their object type.
   * @param oid The object id that defines the primary key values
   * @return Array of Criteria instances
   */
  abstract protected function createPKCondition(ObjectId $oid);
  /**
   * Get the name of the database table, where this type is mapped to
   * @return String
   */
  abstract protected function getTableName();
  /**
   * Determine if an attribute is a foreign key
   * @return Boolean
   */
  abstract public function isForeignKey($name);
}
?>
