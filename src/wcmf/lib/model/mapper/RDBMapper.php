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

use Laminas\Db\Adapter\Adapter;

use wcmf\lib\model\mapper\SelectStatement;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PagingInfo;
use wcmf\lib\persistence\PersistenceMapper;
use wcmf\lib\persistence\PersistentObject;

/**
 * RDBMapper defines the interface for mapper classes that map to relational databases.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface RDBMapper extends PersistenceMapper {

  /**
   * Get the database connection.
   * @return mixed
   */
  public function getConnection();

  /**
   * Get the database adapter.
   * @return Adapter or null
   */
  public function getAdapter(): Adapter;

  /**
   * Get the symbol used to quote identifiers.
   * @return string
   */
  public function getQuoteIdentifierSymbol(): string;

  /**
   * Add quotation to a given identifier (like column name).
   * @param string $identifier The identifier string
   * @return string
   */
  public function quoteIdentifier(string $identifier): string;

  /**
   * Add quotation to a given value.
   * @param mixed $value The value
   * @return string
   */
  public function quoteValue($value): string;

  /**
   * Get the table name with the dbprefix added
   * @return string table name
   */
  public function getRealTableName(): string;

  /**
   * Execute a select query on the connection.
   * @param SelectStatement $selectStmt A SelectStatement instance
   * @param PagingInfo $pagingInfo An PagingInfo instance describing which page to load (optional, default: _null_)
   * @return array<mixed> as the result of PDOStatement::fetchAll(PDO::FETCH_ASSOC)
   */
  public function select(SelectStatement $selectStmt, PagingInfo $pagingInfo=null): array;

  /**
   * Construct an object id from given row data
   * @param array<string, int> $data An associative array with the pk column names as keys and pk values as values
   * @return ObjectId
   */
  public function constructOID(array $data): ObjectId;

  /**
   * Render a Criteria instance as string.
   * @param Criteria $criteria The Criteria instance
   * @param string $placeholder Placeholder (':columnName', '?') used instead of the value (optional, default: _null_)
   * @param string $tableName The table name to use (may differ from criteria's type attribute) (optional)
   * @param string $columnName The column name to use (may differ from criteria's attribute attribute) (optional)
   * @return array<string> with condition (string) and placeholder (string)
   */
  public function renderCriteria(Criteria $criteria, string $placeholder=null, string $tableName=null, string $columnName=null): array;

  /**
   * Load objects defined by a select statement.
   * @param SelectStatement $selectStmt A SelectStatement instance
   * @param int $buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (except BuildDepth::REQUIRED, BuildDepth::PROXIES_ONLY) (default: BuildDepth::SINGLE)
   * @param PagingInfo $pagingInfo A reference PagingInfo instance (optional, default: _null_)
   * @param mixed $originalData A reference that will receive the original database data (optional)
   * @return array<PersistentObject>
   */
  public function loadObjectsFromSQL(SelectStatement $selectStmt, int $buildDepth=BuildDepth::SINGLE, ?PagingInfo $pagingInfo=null, &$originalData=null): array;
}
?>
