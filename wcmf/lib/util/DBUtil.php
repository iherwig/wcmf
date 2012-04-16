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
namespace wcmf\lib\util;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\config\InifileParser;
use wcmf\lib\core\Log;
use wcmf\lib\persistence\PersistenceException;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\util\DBUtil;

/**
 * DBUtil provides database helper functions.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DBUtil {

  /**
   * Execute a sql script. Execution is done inside a transaction, which is rolled back in case of failure.
   * @param file The filename of the sql script
   * @param initSection The name of the configuration section that defines the database connection
   * @return True/False wether execution succeeded or not.
   */
  public static function executeScript($file, $initSection) {
    if (file_exists($file)) {
      Log::info('Executing SQL script '.$file.' ...', __CLASS__);

      // find init params
      $parser = InifileParser::getInstance();
      $initParams = null;
      if (($initParams = $parser->getSection($initSection)) === false) {
        throw new ConfigurationException("No '".$initSection."' section given in configfile.");
      }
      // connect to the database using a RDBMapper instance
      $mapper = ObjectFactory::getInstance('persistenceFacade')->getMapperForConfigSection($initSection);
      $connection = $mapper->getConnection();

      Log::debug('Starting transaction ...', __CLASS__);
      $connection->beginTransaction();

      $exception = null;
      $fh = fopen($file, 'r');
      if ($fh) {
        while (!feof($fh)) {
          $command = fgets($fh, 8192);
          if (strlen(trim($command)) > 0) {
            Log::debug('Executing command: '.$command, __CLASS__);
            try {
              $connection->query($command);
            }
            catch(PDOException $ex) {
              $exception = $ex;
              break;
            }
          }
        }
        fclose($fh);
      }
      if ($exception == null) {
        Log::debug('Execution succeeded, committing ...', __CLASS__);
        $connection->commit();
      }
      else {
        Log::error('Execution failed. Reason'.$exception->getMessage(), __CLASS__);
        Log::debug('Rolling back ...', __CLASS__);
        $connection->rollBack();
      }
      Log::debug('Finished SQL script '.$file.'.', __CLASS__);
    }
    else {
      Log::error('SQL script '.$file.' not found.', __CLASS__);
    }
  }

  /**
   * Duplicate a database on the same server (same user). This works only for MySQL databases.
   * @param srcName The name of the source database
   * @param destName The name of the source database
   * @param server The name of the database server
   * @param user The user name
   * @param password The password
   */
  public static function copyDatabase($srcName, $destName, $server, $user, $password) {
    if($srcName && $destName && $server && $user) {
      DBUtil::createDatabase($destName, $server, $user, $password);

      // setup connection
      $dbConnect = mysql_connect($server, $user, $password);
      if (!$dbConnect) {
      	throw new PersistenceException("Couldn't connect to MySql: ".mysql_error());
      }

      // get table list from source database
      $sqlStatement = "SHOW TABLES FROM ".$srcName;
      $tables = mysql_query($sqlStatement, $dbConnect);
      if ($tables) {
        while($row = mysql_fetch_row($tables)) {
      	// create new table
          $sqlStatement = "CREATE TABLE ".$destName.".".$row[0]." LIKE ".$srcName.".".$row[0];
          Log::debug($sqlStatement, __CLASS__);
          $result = mysql_query($sqlStatement, $dbConnect);
          if (!$result) {
            throw new PersistenceException("Couldn't create table: ".mysql_error());
          }
          mysql_free_result($result);

          // insert data
          $sqlStatement = "INSERT INTO ".$destName.".".$row[0]." SELECT * FROM ".$srcName.".".$row[0];
          Log::debug($sqlStatement, __CLASS__);
          $result = mysql_query($sqlStatement, $dbConnect);
          if (!$result) {
            throw new PersistenceException("Couldn't copy data: ".mysql_error());
          }
          mysql_free_result($result);
        }
        mysql_free_result($tables);
        mysql_close($dbConnect);
      }
      else {
        throw new PersistenceException("Couldn't select tables: ".mysql_error());
      }
    }
  }

  /**
   * Crate a database on the server. This works only for MySQL databases.
   * @param name The name of the source database
   * @param server The name of the database server
   * @param user The user name
   * @param password The password
   */
  public static function createDatabase($name, $server, $user, $password) {
    $created = false;
    if($name && $server && $user) {
      // setup connection
      $dbConnect = mysql_connect($server, $user, $password);
      if (!$dbConnect) {
      	throw new PersistenceException("Couldn't connect to MySql: ".mysql_error());
      }
      // create database
      $sqlStatement = "CREATE DATABASE IF NOT EXISTS ".$name;
      $result = mysql_query($sqlStatement, $dbConnect);
      if ($result) {
        $created = true;
      }
      mysql_free_result($result);
      mysql_close($dbConnect);
      if (!$created) {
	    throw new PersistenceException("Couldn't create database: ".mysql_error());
      }
    }
  }
}
?>
