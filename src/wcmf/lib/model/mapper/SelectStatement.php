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

use \Zend_Db_Select;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\io\FileCache;
use wcmf\lib\model\mapper\RDBMapper;

$includePath = get_include_path();
if (strpos($includePath, 'Zend') === false) {
  set_include_path(get_include_path().PATH_SEPARATOR.WCMF_BASE.'wcmf/vendor/zend');
}
require_once('Zend/Db/Select.php');

/**
 * Select statement
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SelectStatement extends Zend_Db_Select {

  const CACHE_KEY = 'select';

  protected $_id = null;
  protected $_type = null;
  protected $_cachedSql = array();

  /**
   * Get the SelectStatement instance with the given id.
   * If the id is null or is not cached, a new one will be created.
   * @param mapper RDBMapper instance used to retrieve the database connection
   * @param id The statement id
   * @return SelectStatement
   */
  public static function get(RDBMapper $mapper, $id=null) {
    $cacheSection = self::getCacheSection($mapper->getType());
    if ($id == null || !FileCache::exists($cacheSection, $id)) {
      $selectStmt = new SelectStatement($mapper, $id);
    }
    else {
      $selectStmt = FileCache::get($cacheSection, $id);
    }
    return $selectStmt;
  }

  /**
   * Constructor
   * @param mapper RDBMapper instance
   * @param id The statement id
   */
  public function __construct(RDBMapper $mapper, $id=null) {
    parent::__construct($mapper->getConnection());
    $this->_id = $id == null ? __CLASS__.'_'.ObjectId::getDummyId() : $id;
    $this->_type = $mapper->getType();
  }

  /**
   * Destructor
   */
  public function __destruct() {
    $this->save();
  }

  /**
   * Check if the statement is cached already
   * @return Boolean
   */
  public function isCached() {
    return FileCache::exists(self::getCacheSection($this->_type), $this->_id);
  }

  /**
   * Get the cache section
   * @param type The type
   * @return String
   */
  protected static function getCacheSection($type) {
    return self::CACHE_KEY.'_'.$type;
  }

  /**
   * Get the query id
   * @return String
   */
  public function getId() {
    return $this->_id;
  }

  /**
   * Get the types involved in the query
   * @return Array of type names
   */
  public function getInvolvedTypes() {
    $types = array();
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $mapper = $persistenceFacade->getMapper($this->_type);
    $connParams = $mapper->getConnectionParams();
    foreach ($this->getPart(self::FROM) as $name => $from) {
      $tableName = $from['tableName'];
      $types[] = RDBMapper::getTypeForTableName($tableName, $connParams);
    }
    return $types;
  }

  /**
   * Add to bind variables
   * @param bind
   */
  public function addBind(array $bind) {
    $this->_bind = array_merge($this->_bind, $bind);
  }

  /**
   * Execute a count query and return the row count
   * @return integer
   */
  public function getRowCount() {
    // empty columns, order and limit
    $columnPart = $this->_parts[self::COLUMNS];
    $orderPart = $this->_parts[self::ORDER];
    $limitCount = $this->_parts[self::LIMIT_COUNT];
    $limitOffset = $this->_parts[self::LIMIT_OFFSET];
    $this->_parts[self::COLUMNS] = self::$_partsInit[self::COLUMNS];
    $this->_parts[self::ORDER] = self::$_partsInit[self::ORDER];
    $this->_parts[self::LIMIT_COUNT] = self::$_partsInit[self::LIMIT_COUNT];
    $this->_parts[self::LIMIT_OFFSET] = self::$_partsInit[self::LIMIT_OFFSET];

    // do count query
    $this->columns(array('nRows' => SQLConst::COUNT()));
    $stmt = $this->getAdapter()->prepare($this->assemble());
    $stmt->execute($this->getBind());
    $row = $stmt->fetch();
    $nRows = $row['nRows'];

    // reset columns and order
    $this->_parts[self::COLUMNS] = $columnPart;
    $this->_parts[self::ORDER] = $orderPart;
    $this->_parts[self::LIMIT_COUNT] = $limitCount;
    $this->_parts[self::LIMIT_OFFSET] = $limitOffset;

    return $nRows;
  }

  /**
   * Put the statement into the cache
   */
  public function save() {
    FileCache::put(self::getCacheSection($this->_type), $this->_id, $this);
  }

  /**
   * @see Select::assemble()
   */
  public function assemble() {
    $cacheKey = $this->getCacheKey();
    if (!isset($this->_cachedSql[$cacheKey])) {
      $sql = parent::assemble();
      $this->_cachedSql[$cacheKey] = $sql;
      \wcmf\lib\core\Log::error("BUILD: ".$cacheKey, __CLASS__);
    }
    else {
      \wcmf\lib\core\Log::error("REUSE: ".$cacheKey, __CLASS__);
    }
    return $this->_cachedSql[$cacheKey];
  }

  /**
   * Get a unique string for the current parts
   * @return String
   */
  protected function getCacheKey() {
    return json_encode($this->_parts);
  }

  /**
   * Serialization handlers
   */

  public function __sleep() {
    return array('_type', '_cachedSql', '_parts');
  }

  public function __wakeup() {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $mapper = $persistenceFacade->getMapper($this->_type);
    $this->_adapter = $mapper->getConnection();
  }
}
?>