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
define('WCMF_BASE', realpath( dirname(__FILE__).'/../..').'/');
error_reporting(E_ERROR | E_PARSE);

require_once(WCMF_BASE."wcmf/lib/core/ClassLoader.php");

use \Exception;
use wcmf\lib\config\InifileConfiguration;
use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\io\FileUtil;
use wcmf\lib\util\DBUtil;

Log::configure('log4php.properties');
Log::info("updating wCMF database tables...", "dbupdate");

// get configuration from file
$configPath = realpath('../config/').'/';
$configFile = $configPath.'config.ini';
Log::info("configuration file: ".$configFile, "dbupdate");
$config = new InifileConfiguration($configPath);
$config->addConfiguration($configFile);

if (!ensureDatabases($config)) {
  exit();
}

// parse tables.sql
$tables = array();
$readingTable = false;
$tableDef = '';
$lines = file('tables.sql');
foreach($lines as $line)
{
  $line = trim($line);
  if(strlen($line) > 0)
  {
    // check table start
    if (preg_match('/CREATE\s+TABLE/', $line)) {
      // table definition
      $readingTable = true;
    }
    // add line to table definition
    if ($readingTable) {
      $tableDef .= $line."\n";
    }
    // check table end
    if ($readingTable && strpos($line, ';') !== false)
    {
      // end table definition
      $readingTable = false;
      processTableDef($tableDef, $tables);
      $tableDef = '';
    }
  }
}

// process table definitions
$persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
foreach ($tables as $tableDef)
{
  Log::info(("processing table ".$tableDef['name']."..."), "dbupdate");
  $mapper = $persistenceFacade->getMapper($tableDef['entityType']);
  $connection = $mapper->getConnection();
  $connection->beginTransaction();

  if (ensureUpdateTable($connection))
  {
    $oldValue = getOldValue($connection, $tableDef['id'], null, 'table');
    $oldColumns = getMetaData($connection, $tableDef['name']);

    // check if the table already has an update entry
    if ($oldValue == null && $oldColumns === null) {
      // the table has no update entry and does not exist
      createTable($connection, $tableDef);
    }
    else
    {
      if ($oldValue != null && $oldColumns === null)
      {
        // the old table needs to be renamed
        alterTable($connection, $oldValue['table'], $tableDef['name']);
        $oldColumns = getMetaData($connection, $tableDef['name']);
      }
      // the table has an update entry and/or exists
      updateColumns($connection, $tableDef, $oldColumns);
    }
    // update table update entry
    updateEntry($connection, $tableDef);
  }
  $connection->commit();
}

// execute custom scripts from the directory 'custom-dbupdate'
if (is_dir('custom-dbupdate'))
{
  $sqlScripts = FileUtil::getFiles('custom-dbupdate', '/[^_]+_.*\.sql$/', true);
  sort($sqlScripts);
  foreach ($sqlScripts as $script)
  {
    // extract the initSection from the filename
    $initSection = array_shift(preg_split('/_/', basename($script)));
    DBUtil::executeScript($script, $initSection);
  }
}

Log::info("done.", "dbupdate");


/**
 * Ensure the existance of the databases (only mysql)
 * @param parser The inifile parser
 * @return True/False
 */
function ensureDatabases($parser)
{
  $requiredInikeys = array('dbHostName', 'dbName', 'dbUserName', 'dbPassword', 'dbType');
  $createdDatabases = array();
  // check all initparams sections for database connections
  $initSections = array_values($parser->getSection('initparams'));
  foreach ($initSections as $sectionName)
  {
    $sectionData = $parser->getSection($sectionName);
    if (sizeof(array_intersect($requiredInikeys, array_keys($sectionData))) == 5)
    {
      // the section contains the required database connection parameters
      if (strtolower($sectionData['dbType']) == 'mysql')
      {
        $dbKey = join(':', array_values($sectionData));
        if (!in_array($dbKey, $createdDatabases))
        {
          Log::info('Creating database '.$sectionData['dbName'], "dbupdate");
          DBUtil::createDatabase($sectionData['dbName'], $sectionData['dbHostName'], $sectionData['dbUserName'], $sectionData['dbPassword']);
          $createdDatabases[] = $dbKey;
        }
      }
    }
  }
  return true;
}

/**
 * Ensure the existance of the update table 'dbupdate'
 * @param connection The database connection
 * @return True/False
 */
function ensureUpdateTable(&$connection)
{
  try {
    $connection->query('SELECT count(*) FROM dbupdate');
  }
  catch (Exception $e) {
    try {
      // the update table does not exist
      $connection->query('CREATE TABLE `dbupdate` (`table_id` VARCHAR(100) NOT NULL, `column_id` VARCHAR(100) NOT NULL, `type` VARCHAR(100) NOT NULL, '.
                                '`table` VARCHAR(255), `column` VARCHAR(255), `updated` DATETIME, PRIMARY KEY (`table_id`, `column_id`, `type`)) ENGINE=MyISAM');
    }
    catch (Exception $e) {
      Log::error('Error creating update table '.$e->getMessage(), "dbupdate");
      return false;
    }
  }
  return true;
}

/**
 * Get the existing table/column definition that is stored in the update table
 * @param connection The database connection
 * @param tableId The id of the table definition
 * @param columnId The id of the column definition (ignored for type table)
 * @param type 'table' or 'column'
 * @return An array with keys 'table' and 'column' or null if not stored
 */
function getOldValue(&$connection, $tableId, $columnId, $type)
{
  $result = null;
  if ($type == 'column')
  {
    // selection for columns
    $st = $connection->prepare('SELECT * FROM `dbupdate` WHERE `table_id`=? AND `column_id`=? AND `type`=\'column\'');
    $st->execute(array($tableId, $columnId));
    $result = $st->fetchAll(PDO::FETCH_ASSOC);
  }
  else
  {
    // selection for tables
    $st = $connection->prepare('SELECT * FROM `dbupdate` WHERE `table_id`=? AND `type`=\'table\'');
    $st->execute(array($tableId));
    $result = $st->fetchAll(PDO::FETCH_ASSOC);
  }
  if (sizeof($result) > 0)
  {
    $data = $result[0];
    return array('table' => $data['table'], 'column' => $data['column']);
  }
  return null;
}

/**
 * Store a table/column definition in the update table
 * @param connection The database connection
 * @param tableId The id of the table definition
 * @param columnId The id of the column definition
 * @param type 'table' or 'column'
 * @param table The table name
 * @param column The column name
 */
function updateValue(&$connection, $tableId, $columnId, $type, $table, $column)
{
  $oldValue = getOldValue($connection, $tableId, $columnId, $type);
  $result = false;
  try {
    if ($oldValue === null)
    {
      $st = $connection->prepare('INSERT INTO `dbupdate` (`table_id`, `column_id`, `type`, `table`, `column`, `updated`) VALUES (?, ?, ?, ?, ?, ?)');
      $result = $st->execute(array($tableId, $columnId, $type, $table, $column, date("Y-m-d H:i:s")));
    }
    else
    {
      $st = $connection->prepare('UPDATE `dbupdate` SET `table`=?, `column`=?, `updated`=? WHERE `table_id`=? AND `column_id`=? AND `type`=?');
      $result = $st->execute(array($table, $column, date("Y-m-d H:i:s"), $tableId, $columnId, $type));
    }
  }
  catch (Exception $e) {
    Log::error('Error inserting/updating entry '.$e->getMessage(), "dbupdate");
  }
}

/**
 * Store a table/column definition in the update table
 * @param connection The database connection
 * @param tableDef The table definition array as provided by processTableDef
 */
function updateEntry($connection, $tableDef)
{
  updateValue($connection, $tableDef['id'], '-', 'table', $tableDef['name'], '-');
  foreach ($tableDef['columns'] as $columnDef)
  {
    if ($columnDef['id']) {
      updateValue($connection, $tableDef['id'], $columnDef['id'], 'column', $tableDef['name'], $columnDef['name']);
    }
  }
}

/**
 * Create a table
 * @param connection The database connection
 * @param tableDef The table definition array as provided by processTableDef
 */
function createTable(&$connection, $tableDef)
{
  Log::info("> create table '".$tableDef['name']."'", "dbupdate");
  $sql = $tableDef['create'];
  try {
    $connection->query($sql);
  }
  catch (Exception $e) {
    Log::error('Error creating table '.$e->getMessage()."\n".$sql, "dbupdate");
  }
}

/**
 * Alter a table
 * @param connection The database connection
 * @param oldName The old name
 * @param name The new name
 */
function alterTable(&$connection, $oldName, $name)
{
  Log::info("> alter table '".$name."'", "dbupdate");
  $sql = 'ALTER TABLE `'.$oldName.'` RENAME `'.$name.'`';
  try {
    $connection->query($sql);
  }
  catch (Exception $e) {
    Log::error('Error altering table '.$e->getMessage()."\n".$sql, "dbupdate");
  }
}

/**
 * Create a column
 * @param connection The database connection
 * @param table The name of the table
 * @param columnDef An associative array with keys 'name' and 'type'
 */
function createColumn(&$connection, $table, $columnDef)
{
  Log::info("> create column '".$table.".".$columnDef['name'], "dbupdate");
  $sql = 'ALTER TABLE `'.$table.'` ADD `'.$columnDef['name'].'` '.$columnDef['type'];
  try {
    $connection->query($sql);
  }
  catch (Exception $e) {
    Log::error('Error creating column '.$e->getMessage()."\n".$sql, "dbupdate");
  }
}

/**
 * Alter a column
 * @param connection The database connection
 * @param table The name of the table
 * @param oldColumnDef An associative array with keys 'name' and 'type'
 * @param columnDef An associative array with keys 'name' and 'type'
 */
function alterColumn(&$connection, $table, $oldColumnDef, $columnDef)
{
  Log::info("> alter column '".$table.".".$columnDef['name'], "dbupdate");
  $sql = 'ALTER TABLE `'.$table.'` CHANGE `'.$oldColumnDef['name'].'` `'.$columnDef['name'].'` '.$columnDef['type'];
  try {
    $connection->query($sql);
  }
  catch (Exception $e) {
    Log::error('Error altering column '.$e->getMessage()."\n".$sql, "dbupdate");
  }
}

/**
 * Update the columns of a table
 * @param connection The database connection
 * @param tableDef The table definition array as provided by processTableDef
 * @param columnDefs The column definitions as provided by conncetion->MetaColumns
 */
function updateColumns(&$connection, $tableDef, $oldColumnDefs)
{
  foreach ($tableDef['columns'] as $columnDef)
  {
    Log::debug("> process column '".$columnDef['name'], "dbupdate");
    $oldValue = getOldValue($connection, $tableDef['id'], $columnDef['id'], 'column');
    if ($oldValue) {
      $oldColumnDef = $oldColumnDefs[$oldValue['column']];
    }
    else {
      $oldColumnDef = $oldColumnDefs[$columnDef['name']];
    }
    // translate oldColumnDef type
    $oldColumnType = strtoupper($oldColumnDef['Type']);
    if ($oldColumnDef['Null'] == 'NO') {
      $oldColumnType .= ' NOT NULL';
    }
    $oldColumnDefTransl = array('name' => $oldColumnDef['Field'], 'type' => $oldColumnType);

    if ($oldValue === null && $oldColumnDef === null)
    {
      // the column has no update entry and does not exist
      createColumn($connection, $tableDef['name'], $columnDef);
    }
    else if (($oldValue != null && $oldValue['column'] != $columnDef['name']) || strtolower($oldColumnDefTransl['type']) != strtolower($columnDef['type']))
    {
      // ignore changes in 'not null' for primary keys ('not null' is set anyway)
      $typeDiffersInNotNull = strtolower(trim(str_replace($columnDef['type'], "", $oldColumnDefTransl['type']))) == 'not null';
      if ($typeDiffersInNotNull && in_array($columnDef['name'], $tableDef['pks'])) {
        continue;
      }
      // the column has an update entry and does exist
      alterColumn($connection, $tableDef['name'], $oldColumnDefTransl, $columnDef);
    }
  }
}

/**
 * Extract table information from a sql command string
 */
function processTableDef($tableDef, &$tables)
{
  preg_match('/CREATE\s+TABLE\s+`(.*?)`.+entityType=(.*?)\s+tableId=(.*?)\s+\((.*)\)/s', $tableDef, $matches);
  $tableName = $matches[1];
  $entityType = $matches[2];
  $id = $matches[3];
  $tables[$tableName] = array('name' => $tableName, 'create' => $tableDef, 'entityType' => $entityType, 'id' => $id);

  // extract columns/pks
  $columns = array();
  $pks = array();
  $columnDef = preg_split('/\n/', $matches[4]);
  foreach ($columnDef as $columnDef)
  {
    if (strlen(trim($columnDef)) > 0)
    {
      preg_match_all('/`(.*?)`\s+(.*?),([^`]*)/', $columnDef, $matches);
      if (isset($matches))
      {
        $columnNames = $matches[1];
        $columnTypes = $matches[2];
        $comments = $matches[3];
        for($i=0; $i<sizeof($columnNames); $i++)
        {
          preg_match('/columnId=([^\s]+)/', $comments[$i], $matches1);
          if (isset($matches1[1]))
          {
            if ($matches1[1] == 'UNDEFINED') {
              $matches1[1] = '';
            }
            array_push($columns, array('name' => $columnNames[$i], 'type' => $columnTypes[$i], 'id' => $matches1[1]));
          }
        }
      }
      preg_match_all('/PRIMARY KEY \(`(.*?)`\)/', $columnDef, $matches);
      if (isset($matches))
      {
        if (sizeof($matches[1]) > 0) {
          $pks = preg_split('/`\s*,\s*`/', $matches[1][0]);
        }
      }
    }
  }
  $tables[$tableName]['pks'] = $pks;
  $tables[$tableName]['columns'] = $columns;
  Log::debug("processed table: '".$tableName."'", "dbupdate");
  Log::debug($tables[$tableName]['columns'], "dbupdate");
}

/**
 * Get the meta data of a table
 * @return An associative array with the column names as keys and
 * associative arrays with keys 'Field', 'Type', 'Null'[YES|NO], 'Key' [empty|PRI], 'Default', 'Extra' as values
 */
function getMetaData(&$connection, $table)
{
  $result = array();
  try {
    $columns = $connection->query('SHOW COLUMNS FROM '.$table, PDO::FETCH_ASSOC);
    foreach($columns as $key => $col) {
      $result[$col['Field']] = $col;
    }
  }
  catch (Exception $e) {
    return null;
  }
  return $result;
}
