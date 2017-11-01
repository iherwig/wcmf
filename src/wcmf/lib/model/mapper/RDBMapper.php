<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2017 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\model\mapper;

use PDO;
use wcmf\lib\core\EventManager;
use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\core\LogManager;
use wcmf\lib\io\FileUtil;
use wcmf\lib\model\mapper\RDBManyToManyRelationDescription;
use wcmf\lib\model\mapper\RDBMapper;
use wcmf\lib\model\mapper\SelectStatement;
use wcmf\lib\model\Node;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\concurrency\ConcurrencyManager;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\persistence\DeleteOperation;
use wcmf\lib\persistence\impl\AbstractMapper;
use wcmf\lib\persistence\InsertOperation;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PagingInfo;
use wcmf\lib\persistence\PersistenceException;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\persistence\PersistenceMapper;
use wcmf\lib\persistence\PersistenceOperation;
use wcmf\lib\persistence\PersistentObject;
use wcmf\lib\persistence\PersistentObjectProxy;
use wcmf\lib\persistence\ReferenceDescription;
use wcmf\lib\persistence\UpdateOperation;
use wcmf\lib\security\PermissionManager;
use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\TableGateway;

/**
 * RDBMapper maps objects of one type to a relational database schema.
 * It defines a persistence mechanism that specialized mappers customize by overriding
 * the given template methods.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class RDBMapper extends AbstractMapper implements PersistenceMapper {

  const SEQUENCE_CLASS = 'DBSequence';

  private static $adapters = [];      // registry for adapters, key: connId
  private static $inTransaction = []; // registry for transaction status (boolean), key: connId
  private static $isDebugEnabled = false;
  private static $logger = null;

  private $connectionParams = null; // database connection parameters
  private $connId = null;  // a connection identifier composed of the connection parameters
  private $adapter = null; // database adapter
  private $dbPrefix = '';  // database prefix (if given in the configuration file)

  private $relations = null;
  private $attributes = null;

  // prepared statements
  private $idSelectStmt = null;
  private $idInsertStmt = null;
  private $idUpdateStmt = null;

  // keeps track of currently loading relations to avoid circular loading
  private $loadingRelations = [];

  const INTERNAL_VALUE_PREFIX = '_mapper_internal_';

  /**
   * Constructor
   * @param $persistenceFacade
   * @param $permissionManager
   * @param $concurrencyManager
   * @param $eventManager
   */
  public function __construct(PersistenceFacade $persistenceFacade,
          PermissionManager $permissionManager,
          ConcurrencyManager $concurrencyManager,
          EventManager $eventManager) {
    parent::__construct($persistenceFacade, $permissionManager,
            $concurrencyManager, $eventManager);
    if (self::$logger == null) {
      self::$logger = LogManager::getLogger(__CLASS__);
    }
    self::$isDebugEnabled = self::$logger->isDebugEnabled();
  }

  /**
   * Select data to be stored in the session.
   * PDO throws an exception if tried to be (un-)serialized.
   */
  public function __sleep() {
    return ['connectionParams', 'dbPrefix'];
  }

  /**
   * Set the connection parameters.
   * @param $params Initialization data given in an associative array with the following keys:
   *               dbType, dbHostName, dbUserName, dbPassword, dbName
   *               if dbPrefix is given it will be appended to every table string, which is
   *               useful if different applications operate on the same database
   */
  public function setConnectionParams($params) {
    $this->connectionParams = $params;
    if (isset($this->connectionParams['dbPrefix'])) {
      $this->dbPrefix = $this->connectionParams['dbPrefix'];
    }
  }

  /**
   * Get the connection parameters.
   * @return Assoziative array with the following keys:
   *               dbType, dbHostName, dbUserName, dbPassword, dbName, dbPrefix
   */
  public function getConnectionParams() {
    return $this->connectionParams;
  }

  /**
   * Actually connect to the database using the configuration parameters given
   * to the constructor. The implementation ensures that only one connection is
   * used for all RDBMappers with the same configuration parameters.
   */
  private function connect() {
    // connect
    if (isset($this->connectionParams['dbType']) && isset($this->connectionParams['dbHostName']) &&
      isset($this->connectionParams['dbUserName']) && isset($this->connectionParams['dbPassword']) &&
      isset($this->connectionParams['dbName'])) {

      $this->connId = join(',', [$this->connectionParams['dbType'], $this->connectionParams['dbHostName'],
          $this->connectionParams['dbUserName'], $this->connectionParams['dbPassword'], $this->connectionParams['dbName']]);

      // reuse an existing adapter if possible
      if (isset(self::$adapters[$this->connId])) {
        $this->adapter = self::$adapters[$this->connId];
      }
      else {
        try {
          $charSet = isset($this->connectionParams['dbCharSet']) ?
                  $this->connectionParams['dbCharSet'] : 'utf8';
          $dbType = strtolower($this->connectionParams['dbType']);

          // create new connection
          $pdoParams = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
          // driver specific
          switch ($dbType) {
            case 'mysql':
              $pdoParams[PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = true;
              $pdoParams[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES ".$charSet;
              break;
            case 'sqlite':
              if (strtolower($this->connectionParams['dbName']) == ':memory:') {
                $pdoParams[PDO::ATTR_PERSISTENT] = true;
              }
              else {
                $this->connectionParams['dbName'] = FileUtil::realpath(WCMF_BASE.$this->connectionParams['dbName']);
              }
              break;
          }

          $params = [
            'hostname' => $this->connectionParams['dbHostName'],
            'username' => $this->connectionParams['dbUserName'],
            'password' => $this->connectionParams['dbPassword'],
            'database' => $this->connectionParams['dbName'],
            'driver' => 'Pdo_'.ucfirst($this->connectionParams['dbType']),
            'driver_options' => $pdoParams
          ];

          if (!empty($this->connectionParams['dbPort'])) {
            $params['port'] = $this->connectionParams['dbPort'];
          }
          $this->adapter = new Adapter($params);

          // store the connection for reuse
          self::$adapters[$this->connId] = $this->adapter;
        }
        catch(\Exception $ex) {
          throw new PersistenceException("Connection to ".$this->connectionParams['dbHostName'].".".
            $this->connectionParams['dbName']." failed: ".$ex->getMessage());
        }
      }
      // get database prefix if defined
      if (isset($this->connectionParams['dbPrefix'])) {
        $this->dbPrefix = $this->connectionParams['dbPrefix'];
      }
    }
    else {
      throw new IllegalArgumentException("Wrong parameters for constructor.");
    }
  }

  /**
   * Get a new id for inserting into the database
   * @return An id value.
   */
  protected function getNextId() {
    try {
      // get sequence table mapper
      $sequenceMapper = $this->persistenceFacade->getMapper(self::SEQUENCE_CLASS);
      if (!($sequenceMapper instanceof RDBMapper)) {
        throw new PersistenceException(self::SEQUENCE_CLASS." is not mapped by RDBMapper.");
      }
      $sequenceTable = $sequenceMapper->getRealTableName();
      $sequenceConn = $sequenceMapper->getConnection();
      $tableName = strtolower($this->getRealTableName());

      if ($this->idSelectStmt == null) {
        $this->idSelectStmt = $sequenceConn->prepare("SELECT ".$this->quoteIdentifier("id").
                " FROM ".$this->quoteIdentifier($sequenceTable)." WHERE ".
                $this->quoteIdentifier("table")."=".$this->quoteValue($tableName));
      }
      if ($this->idInsertStmt == null) {
        $this->idInsertStmt = $sequenceConn->prepare("INSERT INTO ".
                $this->quoteIdentifier($sequenceTable)." (".$this->quoteIdentifier("id").
                ", ".$this->quoteIdentifier("table").") VALUES (1, ".
                $this->quoteValue($tableName).")");
      }
      if ($this->idUpdateStmt == null) {
        $this->idUpdateStmt = $sequenceConn->prepare("UPDATE ".$this->quoteIdentifier($sequenceTable).
                " SET ".$this->quoteIdentifier("id")."=(".$this->quoteIdentifier("id")."+1) WHERE ".
                $this->quoteIdentifier("table")."=".$this->quoteValue($tableName));
      }
      $this->idSelectStmt->execute();
      $rows = $this->idSelectStmt->fetchAll(PDO::FETCH_ASSOC);
      if (sizeof($rows) == 0) {
        $this->idInsertStmt->execute();
        $this->idInsertStmt->closeCursor();
        $rows = [['id' => 1]];
      }
      $id = $rows[0]['id'];
      $this->idUpdateStmt->execute();
      $this->idUpdateStmt->closeCursor();
      $this->idSelectStmt->closeCursor();
      return $id;
    }
    catch (\Exception $ex) {
      self::$logger->error("The next id query caused the following exception:\n".$ex->getMessage());
      throw new PersistenceException("Error in persistent operation. See log file for details.");
    }
  }

  /**
   * Get the symbol used to quote identifiers.
   * @return String
   */
  public function getQuoteIdentifierSymbol() {
    if ($this->adapter == null) {
      $this->connect();
    }
    return $this->adapter->getPlatform()->getQuoteIdentifierSymbol();
  }

  /**
   * Add quotation to a given identifier (like column name).
   * @param $identifier The identifier string
   * @return String
   */
  public function quoteIdentifier($identifier) {
    if ($this->adapter == null) {
      $this->connect();
    }
    return $this->adapter->getPlatform()->quoteIdentifier($identifier);
  }

  /**
   * Add quotation to a given value.
   * @param $value The value
   * @return String
   */
  public function quoteValue($value) {
    if ($this->adapter == null) {
      $this->connect();
    }
    return $this->adapter->getPlatform()->quoteValue($value);
  }

  /**
   * Get the table name with the dbprefix added
   * @return The table name
   */
  public function getRealTableName() {
    return $this->dbPrefix.$this->getTableName();
  }

  /**
   * Execute a query on the connection.
   * @param $sql The SQL statement as string
   * @param $parameters An array of values to replace the placeholders with (optional, default: empty array)
   * @return If the query is a select, an array of associative arrays containing the selected data,
   * the number of affected rows else
   */
  public function executeSql($sql, $parameters=[]) {
    if ($this->adapter == null) {
      $this->connect();
    }
    try {
      $stmt = $this->adapter->createStatement($sql, $parameters);
      $results = $stmt->execute();
      if ($results->isQueryResult()) {
        return $results->getResource()->fetchAll();
      }
      else {
        return $results->getAffectedRows();
      }
    }
    catch (\Exception $ex) {
      self::$logger->error("The query: ".$sql."\ncaused the following exception:\n".$ex->getMessage());
      throw new PersistenceException("Error in persistent operation. See log file for details.");
    }
  }

  /**
   * Execute a select query on the connection.
   * @param $selectStmt A SelectStatement instance
   * @param $pagingInfo An PagingInfo instance describing which page to load (optional, default: _null_)
   * @return An array as the result of PDOStatement::fetchAll(PDO::FETCH_ASSOC)
   */
  public function select(SelectStatement $selectStmt, PagingInfo $pagingInfo=null) {
    if ($this->adapter == null) {
      $this->connect();
    }
    try {
      if ($pagingInfo != null) {
        // make a count query if requested
        if (!$pagingInfo->isIgnoringTotalCount()) {
          $pagingInfo->setTotalCount($selectStmt->getRowCount());
        }
        // return empty array, if page size <= 0
        if ($pagingInfo->getPageSize() <= 0) {
          return [];
        }
      }
      if (self::$isDebugEnabled) {
        self::$logger->debug("Execute statement: ".$selectStmt->__toString());
        self::$logger->debug($selectStmt->getParameters());
      }
      $result = $selectStmt->query();
      // save statement on success
      $selectStmt->save();
      $rows = $result->fetchAll();
      if (self::$isDebugEnabled) {
        self::$logger->debug("Result: ".sizeof($rows)." row(s)");
      }
      return $rows;
    }
    catch (\Exception $ex) {
      self::$logger->error("The query: ".$selectStmt->__toString()."\ncaused the following exception:\n".$ex->getMessage());
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
    if ($this->adapter == null) {
      $this->connect();
    }

    // transform table name
    $tableName = $this->getRealTableName();

    // translate value names to columns
    $translatedValues = [];
    foreach($operation->getValues() as $name => $value) {
      $attrDesc = $this->getAttribute($name);
      if ($attrDesc) {
        $translatedValues[$attrDesc->getColumn()] = $value;
      }
    }

    // transform criteria
    $where = [];
    foreach ($operation->getCriteria() as $criterion) {
      list($criteriaCondition) = $this->renderCriteria($criterion, null, $tableName);
      $where[] = $criteriaCondition;
    }

    // execute the statement
    $affectedRows = 0;
    $table = new TableGateway($tableName, $this->adapter);
    try {
      if ($operation instanceof InsertOperation) {
        $affectedRows = $table->insert($translatedValues);
      }
      elseif ($operation instanceof UpdateOperation) {
        $affectedRows = $table->update($translatedValues, $where);
      }
      elseif ($operation instanceof DeleteOperation) {
        $affectedRows = $table->delete($where);
      }
      else {
        throw new IllegalArgumentException("Unsupported Operation: ".$operation);
      }
    }
    catch (\Exception $ex) {
      self::$logger->error("The operation: ".$operation."\ncaused the following exception:\n".$ex->getMessage());
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
      return array_values($this->relations['byrole']);
    }
    else {
      return $this->relations[$hierarchyType];
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
    if (isset($this->relations['bytype'][$type])) {
      return $this->relations['bytype'][$type];
    }
    else {
      throw new PersistenceException("No relation to '".$type."' exists in '".$this->getType()."'");
    }
  }

  /**
   * Internal implementation of PersistenceMapper::getRelation()
   * @param $roleName The role name of the relation
   * @param $includeManyToMany Boolean whether to also search in relations to many to many
   *    objects or not
   * @return RelationDescription
   */
  protected function getRelationImpl($roleName, $includeManyToMany) {
    $this->initRelations();
    if (isset($this->relations['byrole'][$roleName])) {
      return $this->relations['byrole'][$roleName];
    }
    elseif ($includeManyToMany && isset($this->relations['nm'][$roleName])) {
      return $this->relations['nm'][$roleName];
    }
    else {
      throw new PersistenceException("No relation to '".$roleName."' exists in '".$this->getType()."'");
    }
  }

  /**
   * Get the relation descriptions defined in the subclass and add them to internal arrays.
   */
  private function initRelations() {
    if ($this->relations == null) {
      $this->relations = [];
      $this->relations['byrole'] = $this->getRelationDescriptions();
      $this->relations['bytype'] = [];
      $this->relations['parent'] = [];
      $this->relations['child'] = [];
      $this->relations['undefined'] = [];
      $this->relations['nm'] = [];

      foreach ($this->relations['byrole'] as $role => $desc) {
        $otherType = $desc->getOtherType();
        if (!isset($this->relations['bytype'][$otherType])) {
          $this->relations['bytype'][$otherType] = [];
        }
        $this->relations['bytype'][$otherType][] = $desc;
        $this->relations['bytype'][$this->persistenceFacade->getSimpleType($otherType)][] = $desc;

        $hierarchyType = $desc->getHierarchyType();
        if ($hierarchyType == 'parent') {
          $this->relations['parent'][] = $desc;
        }
        elseif ($hierarchyType == 'child') {
          $this->relations['child'][] = $desc;
        }
        else {
          $this->relations['undefined'][] = $desc;
        }
        // also store relations to many to many objects, because
        // they would be invisible otherwise
        if ($desc instanceof RDBManyToManyRelationDescription) {
          $nmDesc = $desc->getThisEndRelation();
          $this->relations['nm'][$nmDesc->getOtherRole()] = $nmDesc;
        }
      }
    }
  }

  /**
   * @see PersistenceMapper::getAttributes()
   */
  public function getAttributes(array $tags=[], $matchMode='all') {
    $this->initAttributes();
    $result = [];
    if (sizeof($tags) == 0) {
      $result = array_values($this->attributes['byname']);
    }
    else {
      foreach ($this->attributes['byname'] as $name => $desc) {
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
    if (isset($this->attributes['byname'][$name])) {
      return $this->attributes['byname'][$name];
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
    return $this->attributes['refs'];
  }

  /**
   * Get the relation descriptions defined in the subclass and add them to internal arrays.
   */
  private function initAttributes() {
    if ($this->attributes == null) {
      $this->attributes = [];
      $this->attributes['byname'] = $this->getAttributeDescriptions();
      $this->attributes['refs'] = [];
      foreach ($this->attributes['byname'] as $name => $attrDesc) {
        if ($attrDesc instanceof ReferenceDescription) {
          $this->attributes['refs'][] = $attrDesc;
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
    if (sizeof($sortDef) == 0) {
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
   * @param $name The name of the value
   * @return Boolean
   */
  protected function isPkValue($name) {
    $pkNames = $this->getPKNames();
    return in_array($name, $pkNames);
  }

  /**
   * Construct an object id from given row data
   * @param $data An associative array with the pk column names as keys and pk values as values
   * @return The oid
   */
  public function constructOID($data) {
    $pkNames = $this->getPkNames();
    $ids = [];
    foreach ($pkNames as $pkName) {
      $ids[] = $data[$pkName];
    }
    return new ObjectId($this->getType(), $ids);
  }

  /**
   * Render a Criteria instance as string.
   * @param $criteria The Criteria instance
   * @param $placeholder Placeholder (':columnName', '?') used instead of the value (optional, default: _null_)
   * @param $tableName The table name to use (may differ from criteria's type attribute) (optional)
   * @param $columnName The column name to use (may differ from criteria's attribute attribute) (optional)
   * @return Array with condition (string) and placeholder (string)
   */
  public function renderCriteria(Criteria $criteria, $placeholder=null, $tableName=null, $columnName=null) {
    $type = $criteria->getType();
    if (!$this->persistenceFacade->isKnownType($type)) {
      throw new IllegalArgumentException("Unknown type referenced in Criteria: $type");
    }

    // map type and attribute, if necessary
    $mapper = $this->persistenceFacade->getMapper($type);
    if ($tableName === null) {
      $tableName = $mapper->getRealTableName();
    }
    if ($columnName === null) {
      $attrDesc = $mapper->getAttribute($criteria->getAttribute());
      $columnName = $attrDesc->getColumn();
    }

    $condition = $mapper->quoteIdentifier($tableName).".".$mapper->quoteIdentifier($columnName);
    $operator = $criteria->getOperator();
    $value = $criteria->getValue();
    if (($operator == '=' || $operator == '!=') && $value === null) {
      // handle null values
      $condition .= " IS ".($operator == '!=' ? "NOT " : "")."NULL";
      $placeholder = null;
    }
    elseif (strtolower($operator) == 'in') {
      $array = !$placeholder ? array_map(array($mapper, 'quoteValue'), $value) :
          array_map(function($i) use ($placeholder) { return $placeholder.$i; }, range(0, sizeof($value)-1));
      $condition .= " IN (".join(', ', $array).")";
      $placeholder = !$placeholder ? null : $array;
    }
    else {
      $condition .= " ".$criteria->getOperator()." ";
      $valueStr = !$placeholder ? $mapper->quoteValue($value) : $placeholder;
      $condition .= $valueStr;
    }
    return [$condition, $placeholder];
  }

  /**
   * @see AbstractMapper::loadImpl()
   */
  protected function loadImpl(ObjectId $oid, $buildDepth=BuildDepth::SINGLE) {
    if (self::$isDebugEnabled) {
      self::$logger->debug("Load object: ".$oid->__toString());
    }
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
    if ($buildDepth < 0 && !in_array($buildDepth, [BuildDepth::SINGLE, BuildDepth::REQUIRED])) {
      throw new IllegalArgumentException("Build depth not supported: $buildDepth");
    }
    // create the object
    $object = $this->createObjectFromData([]);

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
            $childObject = $this->persistenceFacade->create($curRelationDesc->getOtherType(), BuildDepth::SINGLE);
          }
          else {
            $childObject = $this->persistenceFacade->create($curRelationDesc->getOtherType(), $newBuildDepth);
          }
          $object->setValue($curRelationDesc->getOtherRole(), [$childObject], true, false);
        }
      }
    }
    return $object;
  }

  /**
   * @see AbstractMapper::saveImpl()
   */
  protected function saveImpl(PersistentObject $object) {
    if ($this->adapter == null) {
      $this->connect();
    }

    // set all missing attributes
    $this->prepareForStorage($object);

    if ($object->getState() == PersistentObject::STATE_NEW) {
      // insert new object
      $operations = $this->getInsertSQL($object);
      foreach($operations as $operation) {
        $mapper = $this->persistenceFacade->getMapper($operation->getType());
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
        $mapper = $this->persistenceFacade->getMapper($operation->getType());
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
    if ($this->adapter == null) {
      $this->connect();
    }

    // log action
    $this->logAction($object);

    // delete object
    $oid = $object->getOID();
    $affectedRows = 0;
    $operations = $this->getDeleteSQL($oid);
    foreach($operations as $operation) {
      $mapper = $this->persistenceFacade->getMapper($operation->getType());
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
        $otherMapper = $this->persistenceFacade->getMapper($otherType);
        $allObjects = $this->loadRelationImpl([$proxy], $relationDesc->getOtherRole());
        $oidStr = $proxy->getOID()->__toString();
        if (isset($allObjects[$oidStr])) {
          foreach($allObjects[$oidStr] as $object) {
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
    }
    // postcondition: the object and all dependend objects are deleted from db
    return true;
  }

  /**
   * Get the database connection.
   * @return PDOConnection
   */
  public function getConnection() {
    if ($this->adapter == null) {
      $this->connect();
    }
    return $this->adapter->getDriver()->getConnection()->getResource();
  }

  /**
   * Get the database adapter.
   * @return Zend\Db\Adapter\AdapterInterface
   */
  public function getAdapter() {
    if ($this->adapter == null) {
      $this->connect();
    }
    return $this->adapter;
  }

  /**
   * @see PersistenceMapper::getOIDsImpl()
   * @note The type parameter is not used here because this class only constructs one type
   */
  protected function getOIDsImpl($type, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null) {
    if ($this->adapter == null) {
      $this->connect();
    }
    $oids = [];

    // create query
    $selectStmt = $this->getSelectSQL($criteria, null, $this->getPkNames(), $orderby, $pagingInfo);
    $data = $this->select($selectStmt, $pagingInfo);
    if (sizeof($data) == 0) {
      return $oids;
    }

    // collect oids
    for ($i=0, $count=sizeof($data); $i<$count; $i++) {
      $oids[] = $this->constructOID($data[$i]);
    }
    return $oids;
  }

  /**
   * @see PersistenceFacade::loadObjectsImpl()
   */
  protected function loadObjectsImpl($type, $buildDepth=BuildDepth::SINGLE, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null) {
    if (self::$isDebugEnabled) {
      self::$logger->debug("Load objects: ".$type);
    }
    $objects = $this->loadObjectsFromQueryParts($type, $buildDepth, $criteria, $orderby, $pagingInfo);
    return $objects;
  }

  /**
   * Load objects defined by several query parts.
   * @param $type The type of the object
   * @param $buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (except BuildDepth::REQUIRED, BuildDepth::PROXIES_ONLY) (default: BuildDepth::SINGLE)
   * @param $criteria An array of Criteria instances that define conditions on the type's attributes (optional, default: _null_)
   * @param $orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (optional, default: _null_)
   * @param $pagingInfo A reference PagingInfo instance (optional, default: _null_)
   * @return Array of PersistentObject instances
   */
  protected function loadObjectsFromQueryParts($type, $buildDepth=BuildDepth::SINGLE, $criteria=null, $orderby=null, PagingInfo $pagingInfo=null) {
    if ($buildDepth < 0 && !in_array($buildDepth, [BuildDepth::INFINITE, BuildDepth::SINGLE])) {
      throw new IllegalArgumentException("Build depth not supported: $buildDepth");
    }

    // create query
    $selectStmt = $this->getSelectSQL($criteria, null, null, $orderby, $pagingInfo);

    $objects = $this->loadObjectsFromSQL($selectStmt, $buildDepth, $pagingInfo);
    return $objects;
  }

  /**
   * Load objects defined by a select statement.
   * @param $selectStmt A SelectStatement instance
   * @param $buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (except BuildDepth::REQUIRED, BuildDepth::PROXIES_ONLY) (default: BuildDepth::SINGLE)
   * @param $pagingInfo A reference PagingInfo instance (optional, default: _null_)
   * @param $originalData A reference that will receive the original database data (optional)
   * @return Array of PersistentObject instances
   */
  public function loadObjectsFromSQL(SelectStatement $selectStmt, $buildDepth=BuildDepth::SINGLE, PagingInfo $pagingInfo=null,
          &$originalData=null) {
    if ($this->adapter == null) {
      $this->connect();
    }
    $objects = [];

    $data = $this->select($selectStmt, $pagingInfo);
    if (sizeof($data) == 0) {
      return $objects;
    }

    if ($originalData !== null) {
      $originalData = $data;
    }

    $tx = $this->persistenceFacade->getTransaction();
    for ($i=0, $count=sizeof($data); $i<$count; $i++) {
      // create the object
      $object = $this->createObjectFromData($data[$i]);

      // don't set the state recursive, because otherwise relations would be initialized
      $object->setState(PersistentObject::STATE_CLEAN);

      $objects[] = $object;
    }

    // attach objects to the transaction
    $attachedObjects = [];
    for ($i=0, $count=sizeof($objects); $i<$count; $i++) {
      $attachedObject = $tx->attach($objects[$i]);
      // don't return objects that are to be deleted by the current transaction
      if ($attachedObject->getState() != PersistentObject::STATE_DELETED) {
        $attachedObjects[] = $attachedObject;
      }
    }

    // add related objects
    // NOTE: This has to be done after registering the objects with the
    // transaction to avoid incomplete objects in case of recursive model dependencies
    $this->addRelatedObjects($attachedObjects, $buildDepth);

    return $attachedObjects;
  }

  /**
   * Create an object of the mapper's type with the given attributes from the given data
   * @param $data An associative array with the attribute names as keys and the attribute values as values
   * @return PersistentObject
   */
  protected function createObjectFromData(array $data) {
    // determine if we are loading or creating
    $createFromLoadedData = (sizeof($data) > 0) ? true : false;

    // initialize data and oid
    $oid = null;
    $initialData = $data;
    if ($createFromLoadedData) {
      $oid = $this->constructOID($initialData);
      // cleanup data
      foreach($initialData as $name => $value) {
        if ($this->hasAttribute($name) || strpos($name, self::INTERNAL_VALUE_PREFIX) === 0) {
          $value = $this->convertValueFromStorage($name, $value);
          $initialData[$name] = $value;
        }
        else {
          unset($initialData[$name]);
        }
      }
    }

    // construct object
    $object = $this->createObject($oid, $initialData);
    return $object;
  }

  /**
   * Convert value after retrieval from storage
   * @param $valueName
   * @param $value
   * @return Mixed
   */
  protected function convertValueFromStorage($valueName, $value) {
    // filter values according to type
    if ($this->hasAttribute($valueName)) {
      $type = $this->getAttribute($valueName)->getType();
      // integer
      if (strpos(strtolower($type), 'int') === 0) {
        $value = (strlen($value) == 0) ? null : intval($value);
      }
    }
    return $value;
  }

  /**
   * Append the child data to a list of object. If the buildDepth does not determine to load a
   * child generation, only the oids of the children will be loaded.
   * @param $objects Array of PersistentObject instances to append the children to
   * @param $buildDepth @see PersistenceFacade::loadObjects()
   */
  protected function addRelatedObjects(array $objects, $buildDepth=BuildDepth::SINGLE) {
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

      $relationId = $role.$relationDesc->getThisRole();
      // if the build depth is not satisfied already and the relation is not
      // currently loading, we load the complete objects and add them
      if ($loadNextGeneration && !isset($this->loadingRelations[$relationId])) {
        $this->loadingRelations[$relationId] = true;
        $relatives = $this->loadRelation($objects, $role, $newBuildDepth);
        // set the values
        foreach ($objects as $object) {
          $oidStr = $object->getOID()->__toString();
          $object->setValue($role, isset($relatives[$oidStr]) ? $relatives[$oidStr] : null, true, false);
        }
        unset($this->loadingRelations[$relationId]);
      }
      // otherwise set the value to not initialized.
      // the Node will initialize it with the proxies for the relation objects
      // on first access
      else {
        foreach ($objects as $object) {
          if ($object instanceof Node) {
            $object->addRelation($role);
          }
        }
      }
    }
  }

  /**
   * @see AbstractMapper::loadRelationImpl()
   */
  protected function loadRelationImpl(array $objects, $role, $buildDepth=BuildDepth::SINGLE,
    $criteria=null, $orderby=null, PagingInfo $pagingInfo=null) {
    if (self::$isDebugEnabled) {
      self::$logger->debug("Load relation: ".$role);
    }
    $relatives = [];
    if (sizeof($objects) == 0) {
      return $relatives;
    }

    $otherRelationDescription = $this->getRelationImpl($role, true);
    if ($otherRelationDescription->getOtherNavigability() == true) {
      $otherType = $otherRelationDescription->getOtherType();
      $otherMapper = $this->persistenceFacade->getMapper($otherType);
      if (!($otherMapper instanceof RDBMapper)) {
        throw new PersistenceException("Can only load related objects, if they are mapped by an RDBMapper instance.");
      }

      // load related objects from other mapper
      $relatedObjects = [];
      $thisRole = $otherRelationDescription->getThisRole();
      $thisRelationDescription = $otherMapper->getRelationImpl($thisRole, true);
      if ($thisRelationDescription->getOtherNavigability() == true) {
        list($selectStmt, $objValueName, $relValueName) = $otherMapper->getRelationSelectSQL($objects, $thisRole, $criteria, $orderby, $pagingInfo);
        $originalData = [];
        $relatedObjects = $otherMapper->loadObjectsFromSQL($selectStmt, ($buildDepth == BuildDepth::PROXIES_ONLY) ? BuildDepth::SINGLE : $buildDepth, $pagingInfo, $originalData);
      }
    }
    // group relatedObjects by original objects
    $relativeMap = [];
    $tx = $this->persistenceFacade->getTransaction();
    foreach ($relatedObjects as $i => $relatedObject) {
      // NOTE: we take the key from the original data, because the corresponding values in the objects might be
      // all the same, if the same object is related to multiple objects in a many to many relation
      // (because only the first related object was attached to the transaction)
      $key = $originalData[$i][$relValueName];
      if (!isset($relativeMap[$key])) {
        $relativeMap[$key] = [];
      }
      $relativeMap[$key][] = ($buildDepth != BuildDepth::PROXIES_ONLY) ? $relatedObject :
        new PersistentObjectProxy($relatedObject->getOID());

      // remove internal value after use (important when loading nm relations,
      // because if not done, the value will not be updated when loading the relation
      // for another object, leading to less objects seen in the relation)
      if (strpos($relValueName, self::INTERNAL_VALUE_PREFIX) === 0) {
        $relatedObject->removeValue($relValueName);
      }
    }
    foreach ($objects as $object) {
      $oidStr = $object->getOID()->__toString();
      $key = $object->getValue($objValueName);
      $relatives[$oidStr] = isset($relativeMap[$key]) ? $relativeMap[$key] : [];
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
    if ($this->adapter == null) {
      $this->connect();
    }
    if (!$this->isInTransaction()) {
      $this->getConnection()->beginTransaction();
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
    if ($this->adapter == null) {
      $this->connect();
    }
    if ($this->isInTransaction()) {
      $this->getConnection()->commit();
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
    if ($this->adapter == null) {
      $this->connect();
    }
    if ($this->isInTransaction()) {
      $this->getConnection()->rollBack();
      $this->setIsInTransaction(false);
    }
  }

  /**
   * Set the transaction state for the connection
   * @param $isInTransaction Boolean whether the connection is in a transaction or not
   */
  protected function setIsInTransaction($isInTransaction) {
    self::$inTransaction[$this->connId] = $isInTransaction;
  }

  /**
   * Check if the connection is currently in a transaction
   * @return Boolean
   */
  protected function isInTransaction() {
    return isset(self::$inTransaction[$this->connId]) && self::$inTransaction[$this->connId] === true;
  }

  /**
   * TEMPLATE METHODS
   * Subclasses must implement this method to define their object type.
   */

  /**
   * Get the names of the attributes in the mapped class to order by default and the sort directions
   * (ASC or DESC). The roleName parameter allows to ask for the order with respect to a specific role.
   * @param $roleName The role name of the relation (optional, default: _null_)
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
   * @param $oid The object id (maybe null)
   * @return PersitentObject
   */
  abstract protected function createObject(ObjectId $oid=null);

  /**
   * Set the object primary key and foreign key values for storing the object in the database.
   * @param $object PersistentObject instance to insert.
   * @note The object does not have the final object id set. If a new id value for a primary key column is needed.
   * @note The prepared object will be used in the application afterwards. So values that are only to be modified for
   * the storage process should be changed in getInsertSQL() and getUpdateSQL() only!
   * for the insert statement, use RDBMapper::getNextId().
   */
  abstract protected function prepareForStorage(PersistentObject $object);

  /**
   * Get the SQL command to select object data from the database.
   * @param $criteria An array of Criteria instances that define conditions on the type's attributes (optional, default: _null_)
   * @param $alias The alias for the table name (default: _null_)
   * @param $attributes An array holding names of attributes to select (optional, default: _null_)
   * @param $orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (optional, default: _null_)
   * @param $pagingInfo An PagingInfo instance describing which page to load (optional, default: _null_))
   * @param $queryId Identifier for the query cache (maybe null to let implementers handle it). (default: _null_)
   * @return SelectStatement instance that selects all object data that match the condition or an array with the query parts.
   * @note The names of the data item columns MUST match the data item names provided in the '_datadef' array from RDBMapper::getObjectDefinition()
   *       Use alias names if not! The selected data will be put into the '_data' array of the object definition.
   */
  abstract public function getSelectSQL($criteria=null, $alias=null, $attributes=null, $orderby=null, PagingInfo $pagingInfo=null, $queryId=null);

  /**
   * Get the SQL command to select those objects from the database that are related to the given object.
   * @note Navigability may not be checked in this method
   * @note In case of a sortable many to many relation, the sortkey value must also be selected
   * @param $otherObjectProxies Array of PersistentObjectProxy instances for the objects to load the relatives for.
   * @param $otherRole The role of the other object in relation to the objects to load.
   * @param $criteria An array of Criteria instances that define conditions on the object's attributes (optional, default: _null_)
   * @param $orderby An array holding names of attributes to order by, maybe appended with 'ASC', 'DESC' (optional, default: _null_)
   * @param $pagingInfo An PagingInfo instance describing which page to load (optional, default: _null_)
   * @return Array with SelectStatement instance and the attribute names which establish the relation between
   * the loaded objects and the proxies (proxies's attribute name first)
   */
  abstract protected function getRelationSelectSQL(array $otherObjectProxies, $otherRole,
          $criteria=null, $orderby=null, PagingInfo $pagingInfo=null);

  /**
   * Get the SQL command to insert a object into the database.
   * @param $object PersistentObject instance to insert.
   * @return Array of PersistenceOperation instances that insert a new object.
   */
  abstract protected function getInsertSQL(PersistentObject $object);

  /**
   * Get the SQL command to update a object in the database.
   * @param $object PersistentObject instance to update.
   * @return Array of PersistenceOperation instances that update an existing object.
   */
  abstract protected function getUpdateSQL(PersistentObject $object);

  /**
   * Get the SQL command to delete a object from the database.
   * @param $oid The object id of the object to delete.
   * @return Array of PersistenceOperation instances that delete an existing object.
   */
  abstract protected function getDeleteSQL(ObjectId $oid);

  /**
   * Create an array of condition Criteria instances for the primary key values
   * @param $oid The object id that defines the primary key values
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
