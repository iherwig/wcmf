<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
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
 */
namespace wcmf\lib\model\mapper;

use \Exception;
use \PDO;
use \Zend_Db;

use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\Node;
use wcmf\lib\model\mapper\RDBManyToManyRelationDescription;
use wcmf\lib\model\mapper\RDBMapper;
use wcmf\lib\model\mapper\SelectStatement;
use wcmf\lib\persistence\AbstractMapper;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\persistence\DeleteOperation;
use wcmf\lib\persistence\InsertOperation;
use wcmf\lib\persistence\UpdateOperation;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PagingInfo;
use wcmf\lib\persistence\PersistenceException;
use wcmf\lib\persistence\PersistenceMapper;
use wcmf\lib\persistence\PersistenceOperation;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\persistence\PersistentObjectProxy;
use wcmf\lib\persistence\ReferenceDescription;

$includePath = get_include_path();
if (strpos($includePath, 'Zend') === false) {
  set_include_path(get_include_path().PATH_SEPARATOR.WCMF_BASE.'wcmf/vendor/zend');
}
require_once('Zend/Db.php');

/**
 * RDBMapper maps objects of one type to a relational database schema.
 * It defines a persistence mechanism that specialized mappers customize by overriding
 * the given template methods.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class RDBMapper extends AbstractMapper implements PersistenceMapper {

  private static $SEQUENCE_CLASS = 'DBSequence';
  private static $connections = array();   // registry for connections, key: connId
  private static $inTransaction = array(); // registry for transaction status (boolean), key: connId

  private $_connectionParams = null; // database connection parameters
  private $_connId = null;     // a connection identifier composed of the connection parameters
  private $_conn = null;       // database connection
  private $_dbPrefix = '';     // database prefix (if given in the configuration file)

  private $_relations = null;
  private $_attributes = null;

  // prepared statements
  private $_idSelectStmt = null;
  private $_idInsertStmt = null;
  private $_idUpdateStmt = null;

  // keeps track of currently loading relations to avoid circular loading
  private $_loadingRelations = array();

  /**
   * Select data to be stored in the session.
   * PDO throws an excetption if tried to be (un-)serialized.
   */
  function __sleep() {
    return array('_connectionParams', '_dbPrefix');
  }

  /**
   * Set the connection parameters.
   * @param params Initialization data given in an assoziative array with the following keys:
   *               dbType, dbHostName, dbUserName, dbPassword, dbName
   *               if dbPrefix is given it will be appended to every table string, which is
   *               usefull if different cms operate on the same database
   */
  public function setConnectionParams($params) {
    $this->_connectionParams = $params;
    if (isset($this->_connectionParams['dbPrefix'])) {
      $this->_dbPrefix = $this->_connectionParams['dbPrefix'];
    }
  }

  /**
   * Get the connection parameters.
   * @return Assoziative array with the following keys:
   *               dbType, dbHostName, dbUserName, dbPassword, dbName, dbPrefix
   */
  public function getConnectionParams() {
    return $this->_connectionParams;
  }

  /**
   * Actually connect to the database using the configuration parameters given
   * to the constructor. The implementation ensures that only one connection is
   * used for all RDBMappers with the same configuration parameters.
   */
  private function connect() {
    // connect
    if (isset($this->_connectionParams['dbType']) && isset($this->_connectionParams['dbHostName']) &&
      isset($this->_connectionParams['dbUserName']) && isset($this->_connectionParams['dbPassword']) &&
      isset($this->_connectionParams['dbName'])) {

      $this->_connId = join(',', array($this->_connectionParams['dbType'], $this->_connectionParams['dbHostName'],
          $this->_connectionParams['dbUserName'], $this->_connectionParams['dbPassword'], $this->_connectionParams['dbName']));

      // reuse an existing connection if possible
      if (isset(self::$connections[$this->_connId])) {
        $this->_conn = self::$connections[$this->_connId];
      }
      else {
        try {
          // create new connection
          $pdoParams = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
          );
          // mysql specific
          if (strtolower($this->_connectionParams['dbType'] == 'mysql')) {
            $pdoParams[PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = true;
            $charSet = isset($this->_connectionParams['dbCharSet']) ?
                    $this->_connectionParams['dbCharSet'] : 'utf8';
            $pdoParams[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES ".$charSet;
          }
          $params = array(
            'host' => $this->_connectionParams['dbHostName'],
            'username' => $this->_connectionParams['dbUserName'],
            'password' => $this->_connectionParams['dbPassword'],
            'dbname' => $this->_connectionParams['dbName'],
            'driver_options' => $pdoParams,
            'profiler' => false
          );
          if (!empty($this->_connectionParams['dbPort'])) {
            $params['port'] = $this->_connectionParams['dbPort'];
          }
          $this->_conn = Zend_Db::factory('Pdo_'.ucfirst($this->_connectionParams['dbType']), $params);
          $this->_conn->setFetchMode(Zend_Db::FETCH_ASSOC);

          // store the connection for reuse
          self::$connections[$this->_connId] = $this->_conn;
        }
        catch(Exception $ex) {
          throw new PersistenceException("Connection to ".$this->_connectionParams['dbHostName'].".".
            $this->_connectionParams['dbName']." failed: ".$ex->getMessage());
        }
      }
      // get database prefix if defined
      if (isset($this->_connectionParams['dbPrefix'])) {
        $this->_dbPrefix = $this->_connectionParams['dbPrefix'];
      }
    }
    else {
      throw new IllegalArgumentException("Wrong parameters for constructor.");
    }
  }

  /**
   * Enable profiling
   */
  public function enableProfiler() {
    if ($this->_conn == null) {
      $this->connect();
    }
    $this->_conn->getProfiler()->setEnabled(true);
  }

  /**
   * Disable profiling
   */
  public function disableProfiler() {
    if ($this->_conn == null) {
      $this->connect();
    }
    $this->_conn->getProfiler()->setEnabled(false);
  }

  /**
   * Get the profiler
   * @return Zend_Db_Profiler
   */
  public function getProfiler() {
    if ($this->_conn == null) {
      $this->connect();
    }
    return $this->_conn->getProfiler();
  }

  /**
   * Get a new id for inserting into the database
   * @return An id value.
   */
  protected function getNextId() {
    try {
      // get sequence table mapper
      $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
      $sequenceMapper = $persistenceFacade->getMapper(self::$SEQUENCE_CLASS);
      if (!($sequenceMapper instanceof RDBMapper)) {
        throw new PersistenceException(self::$SEQUENCE_CLASS." is nor mapped by RDBMapper.");
      }
      $sequenceTable = $sequenceMapper->getTableName();
      $sequenceConn = $sequenceMapper->getConnection();

      if ($this->_idSelectStmt == null) {
        $this->_idSelectStmt = $sequenceConn->prepare("SELECT id FROM ".$sequenceTable);
      }
      if ($this->_idInsertStmt == null) {
        $this->_idInsertStmt = $sequenceConn->prepare("INSERT INTO ".$sequenceTable." (id) VALUES (0)");
      }
      if ($this->_idUpdateStmt == null) {
        $this->_idUpdateStmt = $sequenceConn->prepare("UPDATE ".$sequenceTable." SET id=LAST_INSERT_ID(id+1);");
      }
      $this->_idSelectStmt->execute();
      $rows = $this->_idSelectStmt->fetchAll(PDO::FETCH_ASSOC);
      if (sizeof($rows) == 0) {
        $this->_idInsertStmt->execute();
        $this->_idInsertStmt->closeCursor();
        $rows = array(array('id' => 0));
      }
      $id = $rows[0]['id'];
      $this->_idUpdateStmt->execute();
      $this->_idUpdateStmt->closeCursor();
      $this->_idSelectStmt->closeCursor();
      return $id;
    }
    catch (Exception $ex) {
      Log::error("The next id query caused the following exception:\n".$ex->getMessage(), __CLASS__);
      throw new PersistenceException("Error in persistent operation. See log file for details.");
    }
  }

  /**
   * @see PersistenceMapper::quoteIdentifier
   */
  public function quoteIdentifier($identifier) {
    if ($this->_conn == null) {
      $this->connect();
    }
    return $this->_conn->quoteIdentifier($identifier);
  }

  /**
   * @see PersistenceMapper::quoteValue
   */
  public function quoteValue($value) {
    if ($this->_conn == null) {
      $this->connect();
    }
    return $this->_conn->quote($value);
  }

  /**
   * Get the table name with the dbprefix added
   * @return The table name
   */
  public function getRealTableName() {
    return $this->_dbPrefix.$this->getTableName();
  }

  /**
   * Execute a query on the connection.
   * @param sql The SQL statement as string
   * @param isSelect Boolean whether the statement is a select statement, optional [default: false]
   * @param bindValues An array of data to bind to the placeholders, optional [default: empty array]
   * @return If isSelect is true, an array as the result of PDOStatement::fetchAll(PDO::FETCH_ASSOC),
   * the number of affected rows else
   */
  public function executeSql($sql, $isSelect=false, $bindValues=array()) {
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
   * @param selectStmt A SelectStatement instance
   * @param pagingInfo An PagingInfo instance describing which page to load, optional [default: null]
   * @return An array as the result of PDOStatement::fetchAll(PDO::FETCH_ASSOC)
   */
  protected function select(SelectStatement $selectStmt, PagingInfo $pagingInfo=null) {
    if ($this->_conn == null) {
      $this->connect();
    }
    try {
      if ($pagingInfo != null) {
        // make a count query if requested
        if (!$pagingInfo->isIgnoringTotalCount()) {
          // update pagingInfo
          $pagingInfo->setTotalCount($selectStmt->getRowCount());
        }
        // return empty array, if page size <= 0
        if ($pagingInfo->getPageSize() <= 0) {
          return array();
        }
        else {
          // set the limit on the query (NOTE: not supported by all databases)
          $limit = $pagingInfo->getPageSize();
          $offset = $pagingInfo->getOffset();
          $selectStmt->limit($limit, $offset);
        }
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
  public function executeOperation(PersistenceOperation $operation) {
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
    foreach ($operation->getCriteria() as $criterion) {
      $condition = $this->renderCriteria($criterion, '?', $tableName);
      $where[$condition] = $criterion->getValue();
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
  public function getRelations($hierarchyType='all') {
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
  public function getRelation($roleName) {
    return $this->getRelationImpl($roleName, false);
  }

  /**
   * @see PersistenceMapper::getRelationsByType()
   */
  public function getRelationsByType($type) {
    $this->initRelations();
    if (isset($this->_relations['bytype'][$type])) {
      return $this->_relations['bytype'][$type];
    }
    else {
      throw new PersistenceException("No relation to '".$type."' exists in '".$this->getType()."'");
    }
  }

  /**
   * Internal implementation of PersistenceMapper::getRelation()
   * @param roleName The role name of the relation
   * @param includeManyToMany Boolean whether to also search in relations to many to many
   *    objects or not
   * @return RelationDescription
   */
  protected function getRelationImpl($roleName, $includeManyToMany) {
    $this->initRelations();
    if (isset($this->_relations['byrole'][$roleName])) {
      return $this->_relations['byrole'][$roleName];
    }
    elseif ($includeManyToMany && isset($this->_relations['nm'][$roleName])) {
      return $this->_relations['nm'][$roleName];
    }
    else {
      throw new PersistenceException("No relation to '".$roleName."' exists in '".$this->getType()."'");
    }
  }

  /**
   * Get the relation descriptions defined in the subclass and add them to internal arrays.
   */
  private function initRelations() {
    if ($this->_relations == null) {
      $this->_relations = array();
      $this->_relations['byrole'] = $this->getRelationDescriptions();
      $this->_relations['bytype'] = array();
      $this->_relations['parent'] = array();
      $this->_relations['child'] = array();
      $this->_relations['undefined'] = array();
      $this->_relations['nm'] = array();

      foreach ($this->_relations['byrole'] as $role => $desc) {
        $otherType = $desc->getOtherType();
        if (!isset($this->_relations['bytype'][$otherType])) {
          $this->_relations['bytype'][$otherType] = array();
        }
        $this->_relations['bytype'][$otherType][] = $desc;

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
        // also store relations to many to many objects, because
        // they would be invisible otherwise
        if ($desc instanceof RDBManyToManyRelationDescription) {
          $nmDesc = $desc->getThisEndRelation();
          $this->_relations['nm'][$nmDesc->getOtherRole()] = $nmDesc;
        }
      }
    }
  }

  /**
   * @see PersistenceMapper::getAttributes()
   */
  public function getAttributes(array $tags=array(), $matchMode='all') {
    $this->initAttributes();
    $result = array();
    if (sizeof($tags) == 0) {
      $result = array_values($this->_attributes['byname']);
    }
    else {
      foreach ($this->_attributes['byname'] as $name => $desc) {
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
  public function getAttribute($name) {
    $this->initAttributes();
    if (isset($this->_attributes['byname'][$name])) {
      return $this->_attributes['byname'][$name];
    }
    else {
      throw new PersistenceException("No attribute '".$name."' exists in '".$this->getType()."'");
    }
  }

  /**
   * Get the references to other entities
   * @return Array of AttributeDescription instances
   */
  protected function getReferences() {
    $this->initAttributes();
    return $this->_attributes['refs'];
  }

  /**
   * Get the relation descriptions defined in the subclass and add them to internal arrays.
   */
  private function initAttributes() {
    if ($this->_attributes == null) {
      $this->_attributes = array();
      $this->_attributes['byname'] = $this->getAttributeDescriptions();
      $this->_attributes['refs'] = array();
      foreach ($this->_attributes['byname'] as $name => $attrDesc) {
        if ($attrDesc instanceof ReferenceDescription) {
          $this->_attributes['refs'][] = $attrDesc;
        }
      }
    }
  }

  /**
   * @see PersistenceMapper::isSortable()
   */
  public function isSortable($roleName=null) {
    return $this->getSortkey($roleName) != null;
  }

  /**
   * @see PersistenceMapper::getSortkey()
   */
  public function getSortkey($roleName=null) {
    $sortDefs = $this->getDefaultOrder($roleName);
    if (sizeof($sortDefs) > 0 && $sortDefs[0]['isSortkey'] == true) {
      return $sortDefs[0];
    }
    return null;
  }

  /**
   * @see PersistenceMapper::getDefaultOrder()
   */
  public function getDefaultOrder($roleName=null) {
    $sortDef = null;
    $sortType = null;
    if ($roleName != null && $this->hasRelation($roleName) &&
            ($relationDesc = $this->getRelation($roleName)) instanceof RDBManyToManyRelationDescription) {

      // the order may be overriden by the many to many relation class
      $thisRelationDesc = $relationDesc->getThisEndRelation();
      $nmMapper = $thisRelationDesc->getOtherMapper($thisRelationDesc->getOtherType());
      $sortDef = $nmMapper->getOwnDefaultOrder($roleName);
      $sortType = $nmMapper->getType();
    }
    else {
      // default: the order is defined in this mapper
      $sortDef = $this->getOwnDefaultOrder($roleName);
      $sortType = $this->getType();
    }
    // add the sortType parameter to the result
    for ($i=0, $count=sizeof($sortDef); $i<$count; $i++) {
      $sortDef[$i]['sortType'] = $sortType;
    }
    return $sortDef;
  }

  /**
   * Check if a value is a primary key value
   * @param name The name of the value
   * @return True/False
   */
  protected function isPkValue($name) {
    $pkNames = $this->getPKNames();
    return in_array($name, $pkNames);
  }

  /**
   * Construct an object id from given row data
   * @param type The type of object
   * @param data An associative array with the pk column names as keys and pk values as values
   * @return The oid
   */
  protected function constructOID($data) {
    $pkNames = $this->getPkNames();
    $ids = array();
    foreach ($pkNames as $pkName) {
      $ids[] = $data[$pkName];
    }
    return new ObjectId($this->getType(), $ids);
  }

  /**
   * Render a Criteria instance as string.
   * @param criteria The Criteria instance
   * @param placeholder Placeholder (':columnName', '?') used instead of the value, optional [default: null]
   * @param tableName The table name to use (may differ from criteria's type attribute), optional
   * @param columnName The column name to use (may differ from criteria's attribute attribute), optional
   * @return String
   */
  public function renderCriteria(Criteria $criteria, $placeholder=null, $tableName=null, $columnName=null) {
    $type = $criteria->getType();
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    if (!$persistenceFacade->isKnownType($type)) {
      throw new IllegalArgumentException("Unknown type referenced in Criteria: $type");
    }

    // map type and attribute, if necessary
    $mapper = $persistenceFacade->getMapper($type);
    if ($tableName === null) {
      $tableName = $mapper->getRealTableName();
    }
    if ($columnName === null) {
      $attrDesc = $mapper->getAttribute($criteria->getAttribute());
      $columnName = $attrDesc->getColumn();
    }

    $result = $mapper->quoteIdentifier($tableName).".".$mapper->quoteIdentifier($columnName).
                " ".$criteria->getOperator()." ";
    $value = $criteria->getValue();
    $valueStr = !$placeholder ? $mapper->quoteValue($value) : $placeholder;
    if (is_array($value)) {
      $result .= "(".$valueStr.")";
    }
    else {
      $result .= $valueStr;
    }
    return $result;
  }

  /**
   * @see AbstractMapper::loadImpl()
   */
  protected function loadImpl(ObjectId $oid, $buildDepth=BuildDepth::SINGLE) {
    // delegate to loadObjects
    $criteria = $this->createPKCondition($oid);
    $pagingInfo = new PagingInfo(1, true);
    $objects = $this->loadObjects($oid->getType(), $buildDepth, $criteria, null, $pagingInfo);
    if (sizeof($objects) > 0) {
      return $objects[0];
    }
    else {
      return null;
    }
  }

  /**
   * @see AbstractMapper::createImpl()
   * @note The type parameter is not used here because this class only constructs one type
   */
  protected function createImpl($type, $buildDepth=BuildDepth::SINGLE) {
    if ($buildDepth < 0 && !in_array($buildDepth, array(BuildDepth::SINGLE, BuildDepth::REQUIRED))) {
      throw new IllegalArgumentException("Build depth not supported: $buildDepth");
    }
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    // create the object
    $object = $this->createObjectFromData(array());

    // recalculate build depth for the next generation
    $newBuildDepth = $buildDepth;
    if ($buildDepth != BuildDepth::REQUIRED && $buildDepth != BuildDepth::SINGLE && $buildDepth > 0) {
      $newBuildDepth = $buildDepth-1;
    }

    // prevent infinite recursion
    if ($buildDepth < BuildDepth::MAX) {
      $relationDescs = $this->getRelations();

      // set dependend objects of this object
      foreach ($relationDescs as $curRelationDesc) {
        if ( ($curRelationDesc->getHierarchyType() == 'child' && ($buildDepth > 0 ||
          // if BuildDepth::REQUIRED only construct shared/composite children with min multiplicity > 0
          ($buildDepth == BuildDepth::REQUIRED && $curRelationDesc->getOtherMinMultiplicity() > 0 && $curRelationDesc->getOtherAggregationKind() != 'none')
        )) ) {
          $childObject = null;
          if ($curRelationDesc instanceof RDBManyToManyRelationDescription) {
            $childObject = $persistenceFacade->create($curRelationDesc->getOtherType(), BuildDepth::SINGLE);
          }
          else {
            $childObject = $persistenceFacade->create($curRelationDesc->getOtherType(), $newBuildDepth);
          }
          $object->setValue($curRelationDesc->getOtherRole(), array($childObject), true, false);
        }
      }
    }
    return $object;
  }

  /**
   * @see AbstractMapper::saveImpl()
   */
  protected function saveImpl(PersistentObject $object) {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    if ($this->_conn == null) {
      $this->connect();
    }

    // set all missing attributes
    $this->prepareForStorage($object);

    if ($object->getState() == PersistentObject::STATE_NEW) {
      // insert new object
      $operations = $this->getInsertSQL($object);
      foreach($operations as $operation) {
        $mapper = $persistenceFacade->getMapper($operation->getType());
        $mapper->executeOperation($operation);
      }
      // log action
      $this->logAction($object);
    }
    else if ($object->getState() == PersistentObject::STATE_DIRTY) {
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

    $object->setState(PersistentObject::STATE_CLEAN);

    // postcondition: the object is saved to the db
    //                the object state is STATE_CLEAN
    //                attributes are only inserted if their values differ from ''
    return true;
  }

  /**
   * @see AbstractMapper::deleteImpl()
   */
  protected function deleteImpl(PersistentObject $object) {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    if ($this->_conn == null) {
      $this->connect();
    }

    // log action
    if ($this->isLogging()) {
      $this->logAction($object);
    }

    // delete object
    $oid = $object->getOID();
    $affectedRows = 0;
    $operations = $this->getDeleteSQL($oid);
    foreach($operations as $operation) {
      $mapper = $persistenceFacade->getMapper($operation->getType());
      $affectedRows += $mapper->executeOperation($operation);
    }
    // only delete children if the object was deleted
    if ($affectedRows > 0) {
      $proxy = new PersistentObjectProxy($oid);
      $relationDescs = $this->getRelations('child');
      foreach($relationDescs as $relationDesc) {
        $isManyToMany = ($relationDesc instanceof RDBManyToManyRelationDescription);
        $isComposite = ($relationDesc->getOtherAggregationKind() == 'composite' ||
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
                BuildDepth::SINGLE);
        foreach($objects as $object) {
          if ($isManyToMany) {
            // delete the many to many object immediatly
            $otherMapper->delete($object);
          }
          elseif ($isComposite) {
            // delete composite and relation object children
            $object->delete();
          }
          else {
            // unlink shared children
            $object->setValue($relationDesc->getThisRole(), null, true, false);
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
  public function getConnection() {
    if ($this->_conn == null) {
      $this->connect();
    }
    return $this->_conn;
  }

  /**
   * @see PersistenceMapper::getOIDsImpl()
   * @note The type parameter is not used here because this class only constructs one type
   */
  protected function getOIDsImpl($type, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null) {
    $oids = array();

    // create query (load only pk columns and no children oids)
    $type = $this->getType();
    $objects = $this->loadObjectsFromQueryParts($type, BuildDepth::SINGLE, $criteria, $orderby,
            $pagingInfo);

    // collect oids
    for ($i=0; $i<sizeof($objects); $i++) {
      $oids[] = $objects[$i]->getOID();
    }
    return $oids;
  }

  /**
   * @see PersistenceFacade::loadObjectsImpl()
   */
  protected function loadObjectsImpl($type, $buildDepth=BuildDepth::SINGLE, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null) {
    $objects = $this->loadObjectsFromQueryParts($type, $buildDepth, $criteria, $orderby, $pagingInfo);
    return $objects;
  }

  /**
   * Load the objects of the own type that are related to a given object. The implementation must
   * check the navigability of the relation and return null, if the requested direction is not navigable.
   * @param otherObjectProxy A PersistentObjectProxy for the object that the objects to load are related to
   * @param otherRole The role of the other object in relation to the objects to load
   * @param buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (except BuildDepth::REQUIRED, BuildDepth::PROXIES_ONLY) [default: BuildDepth::SINGLE]
   * @param criteria An array of Criteria instances that define conditions on the objects's attributes (maybe null). [default: null]
   * @param orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (maybe null). [default: null]
   * @param pagingInfo A reference PagingInfo instance (maybe null). [default: null]
   * @return Array of PersistentObject instances or null, if not navigable
   */
  protected function loadRelatedObjects(PersistentObjectProxy $otherObjectProxy, $otherRole, $buildDepth=BuildDepth::SINGLE,
    $criteria=null, $orderby=null, PagingInfo $pagingInfo=null) {
    if ($buildDepth < 0 && !in_array($buildDepth, array(BuildDepth::INFINITE, BuildDepth::SINGLE))) {
      throw new IllegalArgumentException("Build depth not supported: $buildDepth");
    }
    $objects = array();
    $relationDescription = $this->getRelationImpl($otherRole, true);
    if ($relationDescription->getOtherNavigability() == true) {
      $type = $this->getType();

      // create query
      $selectStmt = $this->getRelationSelectSQL($otherObjectProxy, $otherRole, $criteria, $orderby);
      $objects = $this->loadObjectsFromSQL($selectStmt, $buildDepth, $pagingInfo);
    }
    return $objects;
  }

  /**
   * Load objects defined by several query parts.
   * @param type The type of the object
   * @param buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (except BuildDepth::REQUIRED, BuildDepth::PROXIES_ONLY) [default: BuildDepth::SINGLE]
   * @param criteria An array of Criteria instances that define conditions on the type's attributes (maybe null). [default: null]
   * @param orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (maybe null). [default: null]
   * @param joins An array holding join conditions if other tables are involved in this query (maybe null). [default: null]
   * @param pagingInfo A reference PagingInfo instance (maybe null). [default: null]
   * @return Array of PersistentObject instances
   */
  protected function loadObjectsFromQueryParts($type, $buildDepth=BuildDepth::SINGLE, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null) {
    if ($buildDepth < 0 && !in_array($buildDepth, array(BuildDepth::INFINITE, BuildDepth::SINGLE))) {
      throw new IllegalArgumentException("Build depth not supported: $buildDepth");
    }

    // create query
    $selectStmt = $this->getSelectSQL($criteria, null, $orderby);

    $objects = $this->loadObjectsFromSQL($selectStmt, $buildDepth, $pagingInfo);
    return $objects;
  }

  /**
   * Load objects defined by a select statement.
   * @param selectStmt A SelectStatement instance
   * @param buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (except BuildDepth::REQUIRED, BuildDepth::PROXIES_ONLY) [default: BuildDepth::SINGLE]
   * @param criteria An array of Criteria instances that define conditions on the type's attributes (maybe null). [default: null]
   * @param orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (maybe null). [default: null]
   * @param joins An array holding join conditions if other tables are involved in this query (maybe null). [default: null]
   * @param pagingInfo A reference PagingInfo instance (maybe null). [default: null]
   * @return Array of PersistentObject instances
   */
  public function loadObjectsFromSQL(SelectStatement $selectStmt, $buildDepth=BuildDepth::SINGLE, PagingInfo $pagingInfo=null) {
    if ($this->_conn == null) {
      $this->connect();
    }
    $objects = array();

    $data = $this->select($selectStmt, $pagingInfo);
    if (sizeof($data) == 0) {
      return $objects;
    }

    for ($i=0, $count=sizeof($data); $i<$count; $i++) {
      // create the object
      $object = $this->createObjectFromData($data[$i]);

      // don't set the state recursive, because otherwise relations would be initialized
      $object->setState(PersistentObject::STATE_CLEAN);

      // add related objects
      $this->addRelatedObjects($object, $buildDepth);

      // register the object with the transaction
      $object = ObjectFactory::getInstance('persistenceFacade')->getTransaction()->registerLoaded($object);

      $objects[] = $object;
    }
    return $objects;
  }

  /**
   * Create an object of the mapper's type with the given attributes from the given data
   * @param data An associative array with the attribute names as keys and the attribute values as values
   * @return PersistentObject
   */
  protected function createObjectFromData(array $data) {
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
      $this->applyDataOnLoad($object, $data);
    }
    else {
      $this->applyDataOnCreate($object);
    }
    return $object;
  }

  /**
   * Apply the loaded object data to the object.
   * @note Subclasses must implement this method to define their object type.
   * @param object A reference to the object created with createObject method to which the data should be applied
   * @param objectData An associative array with the data returned by execution of the database select statement
   * 			(given by getSelectSQL).
   */
  protected function applyDataOnLoad(PersistentObject $object, array $objectData) {
    // set object data
    $values = array();
    foreach($objectData as $name => $value) {
      if ($this->hasAttribute($name)) {
        $values[$name] = $value;
      }
    }
    $object->initialize($values);
  }

  /**
   * Apply the default data to the object.
   * @note Subclasses must implement this method to define their object type.
   * @param object A reference to the object created with createObject method to which the data should be applied
   */
  protected function applyDataOnCreate(PersistentObject $object) {
    // set object data
    $values = array();
    $attributeDescriptions = $this->getAttributes();
    foreach($attributeDescriptions as $curAttributeDesc) {
      $name = $curAttributeDesc->getName();
      $values[$name] = $curAttributeDesc->getDefaultValue();
    }
    $object->initialize($values);
  }

  /**
   * Append the child data to an object. If the buildDepth does not determine to load a
   * child generation, only the oids of the children will be loaded.
   * @param object A reference to the object to append the children to
   * @param buildDepth @see PersistenceFacade::loadObjects()
   */
  protected function addRelatedObjects(PersistentObject $object, $buildDepth=BuildDepth::SINGLE) {

    // recalculate build depth for the next generation
    $newBuildDepth = $buildDepth;
    if ($buildDepth != BuildDepth::SINGLE && $buildDepth != BuildDepth::INFINITE && $buildDepth > 0) {
      $newBuildDepth = $buildDepth-1;
    }
    $loadNextGeneration = (($buildDepth != BuildDepth::SINGLE) && ($buildDepth > 0 || $buildDepth == BuildDepth::INFINITE));

    // get dependend objects of this object
    $relationDescs = $this->getRelations();
    foreach($relationDescs as $relationDesc) {
      $role = $relationDesc->getOtherRole();

      $relationId = $object->getOID()->__toString().$role;
      // if the build depth is not satisfied already and the relation is not
      // currently loading, we load the complete objects and add them
      if ($loadNextGeneration && !isset($this->_loadingRelations[$relationId])) {
        $this->_loadingRelations[$relationId] = true;
        $relatives = $this->loadRelation($object, $role, $newBuildDepth);
        // set the value
        $object->setValue($role, $relatives, true, false);
        unset($this->_loadingRelations[$relationId]);
      }
      // otherwise set the value to not initialized.
      // the Node will initialize it with the proxies for the relation objects
      // on first access
      else {
        if ($object instanceof Node) {
          $object->addRelation($role);
        }
      }
    }
  }

  /**
   * @see PersistenceMapper::loadRelationImpl()
   */
  protected function loadRelationImpl(PersistentObject $object, $role, $buildDepth=BuildDepth::SINGLE,
    $criteria=null, $orderby=null, PagingInfo $pagingInfo=null) {

    $relatives = array();

    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $relationDescription = $this->getRelationImpl($role, true);
    if ($relationDescription->getOtherNavigability() == true) {
      $otherType = $relationDescription->getOtherType();
      $otherMapper = $persistenceFacade->getMapper($otherType);

      $relatedObjects = $otherMapper->loadRelatedObjects(
          PersistentObjectProxy::fromObject($object),
          $relationDescription->getThisRole(),
          ($buildDepth == BuildDepth::PROXIES_ONLY) ? BuildDepth::SINGLE : $buildDepth,
          $criteria, $orderby, $pagingInfo
      );

      // create proxies if demanded
      if ($buildDepth == BuildDepth::PROXIES_ONLY) {
        for ($i=0, $count=sizeof($relatedObjects); $i<$count; $i++) {
          $relatedObjects[$i] = new PersistentObjectProxy($relatedObjects[$i]->getOID());
        }
      }

      if ($relationDescription->isMultiValued()) {
        // multi valued
        $relatives = $relatedObjects;
      }
      else {
        // single valued
        $relatives = (sizeof($relatedObjects) > 0) ? $relatedObjects[0] : null;
      }
    }
    return $relatives;
  }

  /**
   * @see PersistenceMapper::beginTransaction()
   * Since all RDBMapper instances with the same connection parameters share
   * one connection, the call will be ignored, if the method was already called
   * for another instance.
   */
  public function beginTransaction() {
    if ($this->_conn == null) {
      $this->connect();
    }
    if (!$this->isInTransaction()) {
      $this->_conn->beginTransaction();
      $this->setIsInTransaction(true);
    }
  }

  /**
   * @see PersistenceMapper::commitTransaction()
   * Since all RDBMapper instances with the same connection parameters share
   * one connection, the call will be ignored, if the method was already called
   * for another instance.
   */
  public function commitTransaction() {
    if ($this->_conn == null) {
      $this->connect();
    }
    if ($this->isInTransaction()) {
      $this->_conn->commit();
      $this->setIsInTransaction(false);
    }
  }

  /**
   * @see PersistenceMapper::rollbackTransaction()
   * @note Rollbacks have to be supported by the database.
   * Since all RDBMapper instances with the same connection parameters share
   * one connection, the call will be ignored, if the method was already called
   * for another instance.
   */
  public function rollbackTransaction() {
    if ($this->_conn == null) {
      $this->connect();
    }
    if ($this->isInTransaction()) {
      $this->_conn->rollBack();
      $this->setIsInTransaction(false);
    }
  }

  /**
   * Set the transaction state for the connection
   * @param isInTransaction Boolean whether the connection is in a transaction or not
   */
  protected function setIsInTransaction($isInTransaction) {
    self::$inTransaction[$this->_connId] = $isInTransaction;
  }

  /**
   * Check if the connection is currently in a transaction
   * @return Boolean
   */
  protected function isInTransaction() {
    return isset(self::$inTransaction[$this->_connId]) && self::$inTransaction[$this->_connId] === true;
  }

  /**
   * TEMPLATE METHODS
   * Subclasses must implement this method to define their object type.
   */

  /**
   * Get the names of the attributes in the mapped class to order by default and the sort directions
   * (ASC or DESC). The roleName parameter allows to ask for the order with respect to a specific role.
   * @param rolename The role name of the relation, maybe null [default: null]
   * @return An array of assciative arrays with the keys sortFieldName and sortDirection (ASC or DESC)
   */
  abstract protected function getOwnDefaultOrder($roleName=null);

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
   * @param oid The object id (maybe null)
   * @return A reference to the created object.
   */
  abstract protected function createObject(ObjectId $oid=null);

  /**
   * Set the object primary key and foreign key values for storing the object in the database.
   * @param object A reference to the object to insert.
   * @note The object does not have the final object id set. If a new id value for a primary key column is needed
   * for the insert statement, use RDBMapper::getNextId().
   */
  abstract protected function prepareForStorage(PersistentObject $object);

  /**
   * Get the SQL command to select object data from the database.
   * @param criteria An array of Criteria instances that define conditions on the type's attributes (maybe null). [default: null]
   * @param alias The alias for the table name [default: null uses none].
   * @param orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (maybe null). [default: null]
   * @param noCache Boolean wheter to allow to return a cached statement or not (maybe null). [default: false]
   * @return SelectStatement instance that selects all object data that match the condition or an array with the query parts.
   * @note The names of the data item columns MUST match the data item names provided in the '_datadef' array from RDBMapper::getObjectDefinition()
   *       Use alias names if not! The selected data will be put into the '_data' array of the object definition.
   */
  abstract public function getSelectSQL($criteria=null, $alias=null, $orderby=null, $noCache=false);

  /**
   * Get the SQL command to select those objects from the database that are related to the given object.
   * @note Navigability may not be checked in this method
   * @note In case of a aortable many to many relation, the sortkey value must also be selected
   * @param otherObjectProxy A PersistentObjectProxy for the object to load the relatives for.
   * @param otherRole The role of the other object in relation to the objects to load.
   * @param criteria An array of Criteria instances that define conditions on the object's attributes (maybe null). [default: null]
   * @param orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (maybe null). [default: null]
   * @return SelectStatement instance
   */
  abstract protected function getRelationSelectSQL(PersistentObjectProxy $otherObjectProxy, $otherRole,
          $criteria=null, $orderby=null);

  /**
   * Get the SQL command to insert a object into the database.
   * @param object A reference to the object to insert.
   * @return Array of PersistenceOperation instances that insert a new object.
   */
  abstract protected function getInsertSQL(PersistentObject $object);

  /**
   * Get the SQL command to update a object in the database.
   * @param object A reference to the object to update.
   * @return Array of PersistenceOperation instances that update an existing object.
   */
  abstract protected function getUpdateSQL(PersistentObject $object);

  /**
   * Get the SQL command to delete a object from the database.
   * @param oid The object id of the object to delete.
   * @return Array of PersistenceOperation instances that delete an existing object.
   */
  abstract protected function getDeleteSQL(ObjectId $oid);

  /**
   * Create an array of condition Criteria instances for the primary key values
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
