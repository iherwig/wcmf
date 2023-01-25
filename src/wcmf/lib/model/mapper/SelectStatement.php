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

use wcmf\lib\core\LogTrait;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\PersistenceException;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
USE Laminas\Db\Sql\TableIdentifier;
use Laminas\Db\Sql\Predicate\Expression;

/**
 * Select statement
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SelectStatement extends Select {
  use LogTrait;

  const NO_CACHE = 'no_cache';
  const CACHE_KEY = 'select';

  protected string $id;
  protected string $type;
  /** @var array<string, mixed> */
  protected array $parameters = [];
  /** @var array<string, mixed> */
  protected array $parametersStripped = [];
  /** @var array<string, mixed> */
  protected array $meta = [];
  /** @var array<string, string> */
  protected array $cachedSql = [];

  private Adapter $adapter;

  /**
   * Get the SelectStatement instance with the given id.
   * If the id equals SelectStatement::NO_CACHE or is not cached, a new one will be created.
   * @param RDBMapper $mapper RDBMapper instance used to retrieve the database adapter
   * @param string $id The statement id (optional, default: _SelectStatement::NO_CACHE_)
   * @return SelectStatement
   */
  public static function get(RDBMapper $mapper, $id=self::NO_CACHE): SelectStatement {
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
   * @param RDBMapper $mapper RDBMapper instance
   * @param string $id The statement id (optional, default: _SelectStatement::NO_CACHE_)
   */
  public function __construct(RDBMapper $mapper, $id=self::NO_CACHE) {
    parent::__construct();
    $this->id = $id;
    $this->type = $mapper->getType();
    $this->adapter = $mapper->getAdapter();
  }

  /**
   * Get the query string
   * @return string
   */
  public function __toString(): string {
    return $this->getSql();
  }

  /**
   * Get the id of the statement
   * @return string
   */
  public function getId(): string {
    return $this->id;
  }

  /**
   * Get the entity type associated with the statement
   * @return string
   */
  public function getType(): string {
    return $this->type;
  }

  /**
   * Check if the statement is cached already
   * @return bool
   */
  public function isCached(): bool {
    $cache = ObjectFactory::getInstance('staticCache');
    return $this->id == self::NO_CACHE ? false :
            $cache->exists(self::getCacheSection($this->type), self::getCacheId($this->id));
  }

  /**
   * Add custom meta value
   * @param string $key
   * @param mixed $value
   */
  public function setMeta(string $key, $value): void {
    $this->meta[$key] = $value;
  }

  /**
   * Get custom meta value
   * @param string $key
   * @return mixed
   */
  public function getMeta(string $key) {
    if (isset($this->meta[$key])) {
      return $this->meta[$key];
    }
    return null;
  }

  /**
   * Set the parameter values to replace the placeholders with when doing the select
   * @param array<string, mixed> $parameters Associative array with placeholders as keys
   */
  public function setParameters($parameters): void {
    $this->parameters = $parameters;
    // store version with colons stripped
    $this->parametersStripped = [];
    foreach ($parameters as $key => $value) {
      $this->parametersStripped[preg_replace('/^:/', '', $key)] = $value;
    }
  }

  /**
   * Get the select parameters
   * @param bool $stripColons Indicates whether to strip the colon character from the parameter name or not (default: false)
   * @return array<string, mixed>
   */
  public function getParameters(bool $stripColons=false): array {
    return $stripColons ? $this->parametersStripped : $this->parameters;
  }

  /**
   * Execute a count query and return the row count
   * @return int
   */
  public function getRowCount(): int {
    $mapper = ObjectFactory::getInstance('persistenceFacade')->getMapper($this->type);
    if ($this->table) {
      $table = !is_array($this->table) ? $this->table : key($this->table);
      // use pk columns for counting
      $countColumns = array_map(function(string $valueName) use ($mapper, $table) {
        return $mapper->quoteIdentifier($table).'.'.$mapper->quoteIdentifier($mapper->getAttribute($valueName)->getColumn());
      }, $mapper->getPkNames());
    }
    else {
      // fallback if table is not defined: take first column
      $adapter = $this->getAdapter();
      $columns = $this->processSelect($adapter->getPlatform());
      $countColumns = $columns ? [$columns[$this->quantifier ? 1 : 0][0][0]] : [];
    }

    $countStatement = clone $this;
    $countStatement->reset(Select::COLUMNS);
    $countStatement->reset(Select::LIMIT);
    $countStatement->reset(Select::OFFSET);
    $countStatement->reset(Select::ORDER);
    $countStatement->reset(Select::GROUP);
    // remove all join columns to prevent errors because of missing group by for non aggregated columns (42000 - 1140)
    $joins = $countStatement->joins ? $countStatement->joins->getJoins() : [];
    $countStatement->reset(Select::JOINS);
    foreach ($joins as $key => $join) {
      // re add join without cols
      $countStatement->join($join["name"], $join["on"], [], $join["type"]);
    }

    // create aggregation column
    // NOTE we expect string quantifier (DISTINCT, ALL) or no quantifier
    $quantifierStr = $this->quantifier ? strval($this->quantifier) : '';
    $expr = new \Laminas\Db\Sql\Expression('COUNT('.$quantifierStr.' '.join(', ', $countColumns).')');
    $countStatement->columns(['nRows' => $expr]);

    $countStatement->id = $this->id != self::NO_CACHE ? $this->id.'-count' : self::NO_CACHE;
    try {
      $result = $countStatement->query();
      $row = $result->fetch();
      // update cache with count query
      $this->cachedSql = array_merge($this->cachedSql, $countStatement->cachedSql);
      return $row['nRows'];
    }
    catch (\Exception $ex) {
      self::logger()->error("The query: ".$countStatement->__toString()."\ncaused the following exception:\n".$ex->getMessage());
      throw new PersistenceException("Error in persistent operation. See log file for details.");
    }
  }

  /**
   * Put the statement into the cache
   */
  public function save(): void {
    if ($this->id != self::NO_CACHE) {
      $cache = ObjectFactory::getInstance('staticCache');
      $cache->put(self::getCacheSection($this->type), self::getCacheId($this->id), $this);
    }
  }

  /**
   * @see Select::join()
   *
   * Create join clause
   *
   * @param  string|array<mixed>|TableIdentifier $name
   * @param  string|Expression $on
   * @param  string|array<mixed> $columns
   * @param  string $type one of the JOIN_* constants
   * @return Select Provides a fluent interface
   */
  public function join($name, $on, $columns=self::SQL_STAR, $type=self::JOIN_INNER): Select {
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
   * @param array<string> $columns Array of columns (@see Select::columns())
   * @param string $joinName The name of the join to which the columns belong
   */
  public function addColumns(array $columns, string $joinName=null): void {
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
   * @param string $table
   * @return array<string>
   */
  public function getAliasNames(string $table): array {
    $names = [];
    if (is_array($this->table) && current($this->table) == $table) {
      $names[] = strval(key($this->table));
    }
    foreach ($this->joins->getJoins() as $join) {
      $joinName = $join['name'];
      if (is_array($joinName) && current($joinName) == $table) {
        $names[] = strval(key($joinName));
      }
    }
    return $names;
  }

  /**
   * Get the sql string for this statement
   * @return String
   */
  public function getSql() {
    if ($this->id == self::NO_CACHE) {
      return trim((new Sql($this->getAdapter()))->buildSqlString($this));
    }

    $cacheKey = self::getCacheId($this->id);
    if (!isset($this->cachedSql[$cacheKey])) {
      $sql = trim((new Sql($this->getAdapter()))->buildSqlString($this));
      $this->cachedSql[$cacheKey] = $sql;
    }
    return $this->cachedSql[$cacheKey];
  }

  /**
   * Execute the statement
   * @return \PDOStatement
   */
  public function query(): \PDOStatement {
    $adapter = $this->getAdapter();
    $sql = $this->getSql();
    // always update offset, since it's most likely not contained in the cache id
    $sql = preg_replace('/OFFSET\s+[0-9]+/i', 'OFFSET '.$this->getRawState(Select::OFFSET), $sql);
    $stmt = $adapter->getDriver()->getConnection()->prepare($sql);
    if (self::logger()->isDebugEnabled()) {
      self::logger()->debug("Execute statement: ".$sql);
      self::logger()->debug($this->getParameters(true));
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
   * @param string $type The type
   * @return string
   */
  protected static function getCacheSection(string $type): string {
    return self::CACHE_KEY.'/'.$type;
  }

  /**
   * Get the compressed cache id from the id
   * @param string $id
   * @return string
   */
  protected static function getCacheId(string $id): string {
    return hash('sha256', $id);
  }

  /**
   * Serialization handlers
   */

  public function __sleep() {
    return ['id', 'type', 'table', 'meta', 'cachedSql', 'specifications'];
  }
}
?>