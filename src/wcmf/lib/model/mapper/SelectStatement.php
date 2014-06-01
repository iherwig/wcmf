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

/**
 * Select statement
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SelectStatement extends Zend_Db_Select {

  const NO_CACHE = 'no_cache';
  const CACHE_KEY = 'select';

  protected $_id = null;
  protected $_type = null;
  protected $_meta = array();
  protected $_cachedSql = array();

  /**
   * Get the SelectStatement instance with the given id.
   * If the id equals SelectStatement::NO_CACHE or is not cached, a new one will be created.
   * @param mapper RDBMapper instance used to retrieve the database connection
   * @param id The statement id, optional [default: NO_CACHE]
   * @return SelectStatement
   */
  public static function get(RDBMapper $mapper, $id=self::NO_CACHE) {
    $cacheSection = self::getCacheSection($mapper->getType());
    $cacheId = self::getCacheId($id);
    if ($id == self::NO_CACHE || !FileCache::exists($cacheSection, $cacheId)) {
      $selectStmt = new SelectStatement($mapper, $id);
    }
    else {
      $selectStmt = FileCache::get($cacheSection, $cacheId);
    }
    return $selectStmt;
  }

  /**
   * Constructor
   * @param mapper RDBMapper instance
   * @param id The statement id, optional [default: NO_CACHE]
   */
  public function __construct(RDBMapper $mapper, $id=self::NO_CACHE) {
    parent::__construct($mapper->getConnection());
    $this->_id = $id;
    $this->_type = $mapper->getType();
  }

  /**
   * Get the entity type associated with the statement
   * @return String
   */
  public function getType() {
    return $this->_type;
  }

  /**
   * Check if the statement is cached already
   * @return Boolean
   */
  public function isCached() {
    return $this->_id == self::NO_CACHE ? false :
            FileCache::exists(self::getCacheSection($this->_type), self::getCacheId($this->_id));
  }

  /**
   * Add customt meta value
   * @param key
   * @param value
   */
  public function setMeta($key, $value) {
    $this->_meta[$key] = $value;
  }

  /**
   * Get customt meta value
   * @param key
   * @return Mixed
   */
  public function getMeta($key) {
    if (isset($this->_meta[$key])) {
      return $this->_meta[$key];
    }
    return null;
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
    $stmt = $this->getAdapter()->prepare($this->assemble('count'));
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
    if ($this->_id != self::NO_CACHE) {
      FileCache::put(self::getCacheSection($this->_type), self::getCacheId($this->_id), $this);
    }
  }

  /**
   * @see Select::assemble()
   */
  public function assemble($cacheKey=null) {
    if (!isset($this->_cachedSql[$cacheKey])) {
      $sql = parent::assemble();
      $this->_cachedSql[$cacheKey] = $sql;
    }
    return $this->_cachedSql[$cacheKey];
  }

  /**
   * @see Select::query()
   */
  public function query($fetchMode = null, $bind = array()) {
    $stmt = $this->getAdapter()->prepare($this->assemble('select'));
    $stmt->execute($this->getBind());
    return $stmt;
  }

  /**
   * Get the cache section
   * @param type The type
   * @return String
   */
  protected static function getCacheSection($type) {
    return self::CACHE_KEY.'/'.$type;
  }

  /**
   * Get the compressed cache id from the id
   * @param id
   * @return String
   */
  protected static function getCacheId($id) {
    return md5($id);
  }

  /**
   * Serialization handlers
   */

  public function __sleep() {
    return array('_id', '_type', '_meta', '_cachedSql', '_parts');
  }

  public function __wakeup() {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $mapper = $persistenceFacade->getMapper($this->_type);
    $this->_adapter = $mapper->getConnection();
  }
}
?>