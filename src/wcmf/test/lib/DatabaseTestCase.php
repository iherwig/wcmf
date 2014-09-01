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
  private static $frameworkReady = false;

  // only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
  private $conn = null;

  public final function getConnection() {
    if (!self::$frameworkReady) {
      TestUtil::initFramework(get_class($this), $this->getName());
      self::$frameworkReady = true;
    }
    if ($this->conn === null) {
      // get connection from first entity type
      $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
      $types = $persistenceFacade->getKnownTypes();
      $mapper = $persistenceFacade->getMapper($types[0]);
      $pdo = $mapper->getConnection()->getConnection();

      // create sqlite db
      $params = $mapper->getConnectionParams();
      if ($params['dbType'] == 'sqlite') {
        $numTables = $pdo->query('SELECT count(*) FROM sqlite_master WHERE type = "table"')->fetchColumn();
        if ($numTables == 0) {
          $schema = file_get_contents(WCMF_BASE.'install/tables_sqlite.sql');
          $pdo->exec($schema);
        }
      }
      $this->conn = $this->createDefaultDBConnection($pdo, $params['dbName']);
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