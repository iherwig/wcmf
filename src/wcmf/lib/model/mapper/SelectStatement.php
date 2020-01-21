<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2020 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\model\mapper;

use wcmf\lib\core\LogManager;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\PersistenceException;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;

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
  protected $parameters = [];
  protected $parametersStripped = [];
  protected $meta = [];
  protected $cachedSql = [];

  private $adapter = null;

  private static $logger = null;

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
      $selectStmt->adapter = $mapper->getAdapter();
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
    $this->adapter = $mapper->getAdapter();
    if (self::$logger == null) {
      self::$logger = LogManager::getLogger(__CLASS__);
    }
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
    // store version with colons stripped
    $this->parametersStripped = array_combine(array_map(function($name) {
        return preg_replace('/^:/', '', $name);
    }, array_keys($this->parameters)), $this->parameters);
  }

  /**
   * Get the select parameters
   * @param $stripColons Indicates whether to strip the colon character from the parameter name or not (default: false)
   * @return Array
   */
  public function getParameters($stripColons=false) {
    return $stripColons ? $this->parametersStripped : $this->parameters;
  }

  /**
   * Execute a count query and return the row count
   * @return Integer
   */
  public function getRowCount() {
    $mapper = ObjectFactory::getInstance('persistenceFacade')->getMapper($this->type);
    if ($this->table) {
      $table = !is_array($this->table) ? $this-> table : key($this->table);
      // use pk columns for counting
      $countColumns = array_map(function($valueName) use ($mapper, $table) {
        return $mapper->quoteIdentifier($table).'.'.$mapper->quoteIdentifier($mapper->getAttribute($valueName)->getColumn());
      }, $mapper->getPkNames());
    }
    else {
      // fallback if table is not defined: take first column
      $adapter = $this->getAdapter();
      $columns = $this->processSelect($adapter->getPlatform());
      $countColumns = [$columns[$this->quantifier ? 1 : 0][0][0]];
    }

    $countStatement = clone $this;
    $countStatement->reset(Select::COLUMNS);
    $countStatement->reset(Select::LIMIT);
    $countStatement->reset(Select::OFFSET);
    $countStatement->reset(Select::ORDER);
    $countStatement->reset(Select::GROUP);
    // remove all join columns to prevent errors because of missing group by for non aggregated columns (42000 - 1140)
    $joins = $countStatement->joins->getJoins();
    $countStatement->reset(Select::JOINS);
    foreach ($joins as $key => $join) {
      // re add join without cols
      $countStatement->join($join["name"], $join["on"], [], $join["type"]);
    }

    // create aggregation column
    $countStatement->columns(['nRows' => new \Laminas\Db\Sql\Expression('COUNT('.$this->quantifier.' '.join(', ', $countColumns).')')]);

    $countStatement->id = $this->id != self::NO_CACHE ? $this->id.'-count' : self::NO_CACHE;
    try {
      $result = $countStatement->query();
      $row = $result->fetch();
      return $row['nRows'];
    }
    catch (\Exception $ex) {
      self::$logger->error("The query: ".$countStatement->__toString()."\ncaused the following exception:\n".$ex->getMessage());
      throw new PersistenceException("Error in persistent operation. See log file for details.");
    }
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
      $joins = [];
      foreach ($this->joins->getJoins() as $join) {
        if ($join['name'] == $joinName || in_array($joinName, array_keys($join['name']))) {
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
   * Get the alias names for a table name
   * @param $tables
   * @return Array
   */
  public function getAliasNames($table) {
    $names = [];
    if (is_array($this->table) && current($this->table) == $table) {
      $names[] = key($this->table);
    }
    foreach ($this->joins->getJoins() as $join) {
      $joinName = $join['name'];
      if (is_array($joinName) && current($joinName) == $table) {
        $names[] = key($joinName);
      }
    }
    return $names;
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
    // always update offset, since it's most likely not contained in the cache id
    $sql = preg_replace('/OFFSET\s+[0-9]+/i', 'OFFSET '.$this->getRawState(Select::OFFSET), $sql);
    $stmt = $adapter->getDriver()->getConnection()->prepare($sql);
    if (self::$logger->isDebugEnabled()) {
      self::$logger->debug("Execute statement: ".$sql);
      self::$logger->debug($this->getParameters(true));
    }
    return $stmt->execute($this->getParameters(true))->getResource();
  }

  /**
   * Get the adapter corresponding to the statement's type
   * @return AdapterInterface
   */
  protected function getAdapter() {
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
    return ['id', 'type', 'meta', 'cachedSql', 'specifications'];
  }

  public function __wakeup() {
    parent::__construct();
    if (self::$logger == null) {
      self::$logger = LogManager::getLogger(__CLASS__);
    }
  }
}
?>