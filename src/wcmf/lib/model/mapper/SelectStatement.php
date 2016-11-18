<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2016 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\model\mapper;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\mapper\RDBMapper;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;

/**
 * Select statement
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SelectStatement extends Select {

  const NO_CACHE = 'no_cache';
  const CACHE_KEY = 'select';

  protected $id = null;
  protected $type = null;
  protected $parameters = array();
  protected $meta = array();
  protected $cachedSql = array();

  private $adapter = null;

  /**
   * Get the SelectStatement instance with the given id.
   * If the id equals SelectStatement::NO_CACHE or is not cached, a new one will be created.
   * @param $mapper RDBMapper instance used to retrieve the database adapter
   * @param $id The statement id (optional, default: _SelectStatement::NO_CACHE_)
   * @return SelectStatement
   */
  public static function get(RDBMapper $mapper, $id=self::NO_CACHE) {
    $cache = ObjectFactory::getInstance('staticCache');
    $cacheSection = self::getCacheSection($mapper->getType());
    $cacheId = self::getCacheId($id);
    if ($id == self::NO_CACHE || !$cache->exists($cacheSection, $cacheId)) {
      $selectStmt = new SelectStatement($mapper, $id);
    }
    else {
      $selectStmt = $cache->get($cacheSection, $cacheId);
    }
    return $selectStmt;
  }

  /**
   * Constructor
   * @param $mapper RDBMapper instance
   * @param $id The statement id (optional, default: _SelectStatement::NO_CACHE_)
   */
  public function __construct(RDBMapper $mapper, $id=self::NO_CACHE) {
    parent::__construct();
    $this->id = $id;
    $this->type = $mapper->getType();
  }

  /**
   * Get the query string
   * @return String
   */
  public function __toString() {
    return $this->getSql();
  }

  /**
   * Get the id of the statement
   * @return String
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Get the entity type associated with the statement
   * @return String
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Check if the statement is cached already
   * @return Boolean
   */
  public function isCached() {
    $cache = ObjectFactory::getInstance('staticCache');
    return $this->id == self::NO_CACHE ? false :
            $cache->exists(self::getCacheSection($this->type), self::getCacheId($this->id));
  }

  /**
   * Add custom meta value
   * @param $key
   * @param $value
   */
  public function setMeta($key, $value) {
    $this->meta[$key] = $value;
  }

  /**
   * Get custom meta value
   * @param $key
   * @return Associative array
   */
  public function getMeta($key) {
    if (isset($this->meta[$key])) {
      return $this->meta[$key];
    }
    return null;
  }

  /**
   * Set the parameter values to replace the placeholders with when doing the select
   * @param $parameters Associative array with placeholders as keys
   */
  public function setParameters($parameters) {
    $this->parameters = $parameters;
  }

  /**
   * Get the select parameters
   * @return Array
   */
  public function getParameters() {
    return $this->parameters;
  }

  /**
   * Execute a count query and return the row count
   * @return Integer
   */
  public function getRowCount() {
    $adapter = $this->getAdapter();
    $sql = preg_replace('/^SELECT (.+) FROM/i', 'SELECT COUNT(*) AS nRows FROM', $this->getSql());
    $sql = preg_replace('/LIMIT [0-9]+ OFFSET [0-9]+$/i', '', $sql);
    $sql = preg_replace('/ORDER BY .+$/i', '', $sql);
    $stmt = $adapter->getDriver()->getConnection()->prepare(trim($sql));
    $result = $stmt->execute($this->getParameters())->getResource();
    $row = $result->fetch();
    $nRows = $row['nRows'];
    return $nRows;
  }

  /**
   * Put the statement into the cache
   */
  public function save() {
    if ($this->id != self::NO_CACHE) {
      $cache = ObjectFactory::getInstance('staticCache');
      $cache->put(self::getCacheSection($this->type), self::getCacheId($this->id), $this);
    }
  }

  /**
   * @see Select::join()
   */
  public function join($name, $on, $columns=self::SQL_STAR, $type=self::JOIN_INNER) {
    // prevent duplicate joins
    foreach ($this->joins->getJoins() as $join) {
      if ($join['name'] == $name) {
        return $this;
      }
    }
    return parent::join($name, $on, $columns, $type);
  }

  /**
   * Add columns to the statement
   * @param $columns Array of columns (@see Select::columns())
   * @param $joinName The name of the join to which the columns belong
   */
  public function addColumns(array $columns, $joinName=null) {
    if ($joinName === null) {
      // add normal column
      $this->columns = $this->columns + $columns;
    }
    else {
      // add column to join
      $joins = array();
      foreach ($this->joins->getJoins() as $join) {
        if ($join['name'] == $joinName) {
          $join['columns'] += $columns;
        }
        $joins[] = $join;
      }
      $this->joins->reset();
      foreach ($joins as $join) {
        parent::join($join['name'], $join['on'], $join['columns'], $join['type']);
      }
    }
  }

  /**
   * Get the sql string for this statement
   * @return String
   */
  public function getSql() {
    $cacheKey = self::getCacheId($this->id);
    if (!isset($this->cachedSql[$cacheKey])) {
      $sql = trim((new Sql($this->getAdapter()))->buildSqlString($this));
      $this->cachedSql[$cacheKey] = $sql;
    }
    return $this->cachedSql[$cacheKey];
  }

  /**
   * Execute the statement
   * @return PDOStatement
   */
  public function query() {
    $adapter = $this->getAdapter();
    $sql = $this->getSql();
    $stmt = $adapter->getDriver()->getConnection()->prepare($sql);
    return $stmt->execute($this->getParameters())->getResource();
  }

  /**
   * Get the adapter corresponding to the statement's type
   * @return AdapterInterface
   */
  protected function getAdapter() {
    if ($this->adapter == null) {
      $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
      $mapper = $persistenceFacade->getMapper($this->type);
      $this->adapter = $mapper->getAdapter();
    }
    return $this->adapter;
  }

  /**
   * Get the cache section
   * @param $type The type
   * @return String
   */
  protected static function getCacheSection($type) {
    return self::CACHE_KEY.'/'.$type;
  }

  /**
   * Get the compressed cache id from the id
   * @param $id
   * @return String
   */
  protected static function getCacheId($id) {
    return md5($id);
  }

  /**
   * Serialization handlers
   */

  public function __sleep() {
    return array('id', 'type', 'meta', 'cachedSql', 'specifications');
  }

  public function __wakeup() {
  }
}
?>