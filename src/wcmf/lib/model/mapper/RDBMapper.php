<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2018 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\model\mapper;

use wcmf\lib\model\mapper\SelectStatement;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\persistence\PagingInfo;
use wcmf\lib\persistence\PersistenceMapper;

/**
 * RDBMapper defines the interface for mapper classes that map to relational databases.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface RDBMapper extends PersistenceMapper {

  /**
   * Get the database connection.
   * @return PDOConnection
   */
  public function getConnection();

  /**
   * Get the database adapter.
   * @return Zend\Db\Adapter\AdapterInterface
   */
  public function getAdapter();

  /**
   * Get the symbol used to quote identifiers.
   * @return String
   */
  public function getQuoteIdentifierSymbol();

  /**
   * Add quotation to a given identifier (like column name).
   * @param $identifier The identifier string
   * @return String
   */
  public function quoteIdentifier($identifier);

  /**
   * Add quotation to a given value.
   * @param $value The value
   * @return String
   */
  public function quoteValue($value);

  /**
   * Get the table name with the dbprefix added
   * @return The table name
   */
  public function getRealTableName();

  /**
   * Execute a select query on the connection.
   * @param $selectStmt A SelectStatement instance
   * @param $pagingInfo An PagingInfo instance describing which page to load (optional, default: _null_)
   * @return An array as the result of PDOStatement::fetchAll(PDO::FETCH_ASSOC)
   */
  public function select(SelectStatement $selectStmt, PagingInfo $pagingInfo=null);

  /**
   * Construct an object id from given row data
   * @param $data An associative array with the pk column names as keys and pk values as values
   * @return The oid
   */
  public function constructOID($data);

  /**
   * Render a Criteria instance as string.
   * @param $criteria The Criteria instance
   * @param $placeholder Placeholder (':columnName', '?') used instead of the value (optional, default: _null_)
   * @param $tableName The table name to use (may differ from criteria's type attribute) (optional)
   * @param $columnName The column name to use (may differ from criteria's attribute attribute) (optional)
   * @return Array with condition (string) and placeholder (string)
   */
  public function renderCriteria(Criteria $criteria, $placeholder=null, $tableName=null, $columnName=null);

  /**
   * Load objects defined by a select statement.
   * @param $selectStmt A SelectStatement instance
   * @param $buildDepth One of the BUILDDEPTH constants or a number describing the number of generations to build
   *        (except BuildDepth::REQUIRED, BuildDepth::PROXIES_ONLY) (default: BuildDepth::SINGLE)
   * @param $pagingInfo A reference PagingInfo instance (optional, default: _null_)
   * @param $originalData A reference that will receive the original database data (optional)
   * @return Array of PersistentObject instances
   */
  public function loadObjectsFromSQL(SelectStatement $selectStmt, $buildDepth=BuildDepth::SINGLE,
          PagingInfo $pagingInfo=null, &$originalData=null);
}
?>
