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

  // only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
  private $conn = null;

  public final function getConnection() {
    if ($this->conn === null) {
      $config = ObjectFactory::getConfigurationInstance();
      $params = $config->getSection('database');
      if (self::$pdo == null) {
        self::$pdo = new PDO($params['dbType'].':host='.$params['dbHostName'].';dbname='.$params['dbName'], $params['dbUserName'], $params['dbPassword']);
      }
      $this->conn = $this->createDefaultDBConnection(self::$pdo, $params['dbName']);
    }

    return $this->conn;
  }

  protected function setUp() {
    // clear object factory instance
    ObjectFactory::clear();

    parent::setUp();
  }
}
?>