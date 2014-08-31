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
namespace wcmf\test\lib;

use \PDO;
use \Zend_Db;
use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;

/**
 * ControllerTestCase is a PHPUnit test case, that
 * serves as base class for test cases used for Controllers.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class DatabaseTestCase extends \PHPUnit_Extensions_Database_TestCase {
  // only instantiate pdo once for test clean-up/fixture load
  private static $pdo = null;
  private static $frameworkReady = false;

  // only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
  private $conn = null;

  public final function getConnection() {
    if (!self::$frameworkReady) {
      TestUtil::initFramework(get_class($this), $this->getName());
      self::$frameworkReady = true;
    }
    if ($this->conn === null) {
      $config = ObjectFactory::getConfigurationInstance();
      $params = $config->getSection('database');
      if (self::$pdo == null) {
        $pdoParams = array();
        if ($params['dbType'] == 'sqlite') {
          $pdoParams[PDO::ATTR_PERSISTENT] = true;
        }

        $conParams = array(
          'host' => $params['dbHostName'],
          'username' => $params['dbUserName'],
          'password' => $params['dbPassword'],
          'dbname' => $params['dbName'],
          'driver_options' => $pdoParams
        );
        self::$pdo = Zend_Db::factory('Pdo_'.ucfirst($params['dbType']), $conParams)->getConnection();
        // create sqlite db
        if ($params['dbType'] == 'sqlite') {
          $numTables = self::$pdo->query('SELECT count(*) FROM sqlite_master WHERE type = "table"')->fetchColumn();
          if ($numTables == 0) {
            $schema = file_get_contents(WCMF_BASE.'install/tables_sqlite.sql');
            self::$pdo->exec($schema);
          }
        }
      }
      $this->conn = $this->createDefaultDBConnection(self::$pdo, $params['dbName']);
    }

    return $this->conn;
  }

  protected function setUp() {
    Log::info("Running: ".get_class($this).".".$this->getName(), __CLASS__);
    if (!self::$frameworkReady) {
      TestUtil::initFramework();
      self::$frameworkReady = true;
    }
    parent::setUp();
  }

  protected function tearDown() {
    self::$frameworkReady = false;
  }
}
?>