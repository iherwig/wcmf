<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\util;

use PDO;
use wcmf\lib\config\ConfigurationException;
use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\core\LogManager;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\PersistenceException;
use Zend_Db;

/**
 * DBUtil provides database helper functions.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DBUtil {

  private static function createConnection($connectionParams) {
    // connect
    if (isset($connectionParams['dbType']) && isset($connectionParams['dbHostName']) &&
      isset($connectionParams['dbUserName']) && isset($connectionParams['dbPassword']) &&
      isset($connectionParams['dbName'])) {

      try {
        // create new connection
        $pdoParams = array(
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        );
        if ($connectionParams['dbType'] == 'mysql') {
          $pdoParams[PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = true;
        }
        $params = array(
          'host' => $connectionParams['dbHostName'],
          'username' => $connectionParams['dbUserName'],
          'password' => $connectionParams['dbPassword'],
          'dbname' => $connectionParams['dbName'],
          'driver_options' => $pdoParams,
          'profiler' => false
        );
        if (!empty($connectionParams['dbPort'])) {
          $params['port'] = $connectionParams['dbPort'];
        }
        $conn = Zend_Db::factory('Pdo_'.ucfirst($connectionParams['dbType']), $params);
        $conn->setFetchMode(Zend_Db::FETCH_ASSOC);
        return $conn;
      }
      catch(\Exception $ex) {
        throw new PersistenceException("Connection to ".$connectionParams['dbHostName'].".".
          $connectionParams['dbName']." failed: ".$ex->getMessage());
      }
    }
    else {
      throw new IllegalArgumentException("Wrong parameters for creating connection.");
    }
  }

  /**
   * Execute a sql script. Execution is done inside a transaction, which is rolled back in case of failure.
   * @param $file The filename of the sql script
   * @param $initSection The name of the configuration section that defines the database connection
   * @return Boolean whether execution succeeded or not.
   */
  public static function executeScript($file, $initSection) {
    $logger = LogManager::getLogger(__CLASS__);
    if (file_exists($file)) {
      $logger->info('Executing SQL script '.$file.' ...');

      // find init params
      $config = ObjectFactory::getInstance('configuration');
      if (($connectionParams = $config->getSection($initSection)) === false) {
        throw new ConfigurationException("No '".$initSection."' section given in configfile.");
      }
      // connect to the database
      $conn = self::createConnection($connectionParams);

      $logger->debug('Starting transaction ...');
      $conn->beginTransaction();

      $exception = null;
      $fh = fopen($file, 'r');
      if ($fh) {
        while (!feof($fh)) {
          $command = fgets($fh, 8192);
          if (strlen(trim($command)) > 0) {
            $logger->debug('Executing command: '.preg_replace('/[\n]+$/', '', $command));
            try {
              $conn->query($command);
            }
            catch(\Exception $ex) {
              $exception = $ex;
              break;
            }
          }
        }
        fclose($fh);
      }
      if ($exception == null) {
        $logger->debug('Execution succeeded, committing ...');
        $conn->commit();
      }
      else {
        $logger->error('Execution failed. Reason'.$exception->getMessage());
        $logger->debug('Rolling back ...');
        $conn->rollBack();
      }
      $logger->debug('Finished SQL script '.$file.'.');
      $conn->closeConnection();
    }
    else {
      $logger->error('SQL script '.$file.' not found.');
    }
  }

  /**
   * Duplicate a database on the same server (same user). This works only for MySQL databases.
   * @param $srcName The name of the source database
   * @param $destName The name of the source database
   * @param $server The name of the database server
   * @param $user The user name
   * @param $password The password
   */
  public static function copyDatabase($srcName, $destName, $server, $user, $password) {
    $logger = LogManager::getLogger(__CLASS__);
    if ($srcName && $destName && $server && $user) {
      self::createDatabase($destName, $server, $user, $password);

      // setup connection
      $conn =null;
      try {
        $conn = new PDO("mysql:host=$server", $user, $password);
      }
      catch(\Exception $ex) {
      	throw new PersistenceException("Couldn't connect to MySql: ".$ex->getMessage());
      }

      $conn->beginTransaction();
      try {
        // get table list from source database
        foreach ($conn->query("SHOW TABLES FROM ".$srcName) as $row) {
          // create new table
          $sqlStmt = "CREATE TABLE ".$destName.".".$row[0]." LIKE ".$srcName.".".$row[0];
          $logger->debug($sqlStmt);
          $result = $conn->query($sqlStmt);
          if (!$result) {
            throw new PersistenceException("Couldn't create table: ".$conn->errorInfo());
          }

          // insert data
          $sqlStmt = "INSERT INTO ".$destName.".".$row[0]." SELECT * FROM ".$srcName.".".$row[0];
          $logger->debug($sqlStmt);
          $result = $conn->query($sqlStmt);
          if (!$result) {
            throw new PersistenceException("Couldn't copy data: ".$conn->errorInfo());
          }
          $conn->commit();
        }
      } catch (\Exception $ex) {
        $conn->rollback();
      }
    }
  }

  /**
   * Crate a database on the server. This works only for MySQL databases.
   * @param $name The name of the source database
   * @param $server The name of the database server
   * @param $user The user name
   * @param $password The password
   */
  public static function createDatabase($name, $server, $user, $password) {
    $created = false;
    if($name && $server && $user) {
      // setup connection
      $conn =null;
      try {
        $conn = new PDO("mysql:host=$server", $user, $password);
      }
      catch(\Exception $ex) {
      	throw new PersistenceException("Couldn't connect to MySql: ".$ex->getMessage());
      }
      // create database
      $sqlStmt = "CREATE DATABASE IF NOT EXISTS ".$name;
      $result = $conn->query($sqlStmt);
      if ($result) {
        $created = true;
      }
      if (!$created) {
  	    throw new PersistenceException("Couldn't create database: ".$conn->errorInfo());
      }
    }
  }
}
?>
