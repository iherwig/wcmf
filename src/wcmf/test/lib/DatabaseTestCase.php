<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2015 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\test\lib;

use wcmf\lib\core\LogManager;
use wcmf\lib\util\TestUtil;

/**
 * DatabaseTestCase is the base class for test cases that need database support.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class DatabaseTestCase extends \PHPUnit_Extensions_Database_TestCase {
  private static $frameworkReady = false;

  // only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
  private $conn = null;

  public final function getConnection() {
    if (!self::$frameworkReady) {
      TestUtil::initFramework(WCMF_BASE.'app/config/');
      self::$frameworkReady = true;
    }
    if ($this->conn === null) {
      $params = TestUtil::createDatabase();
      $this->conn = $this->createDefaultDBConnection($params['connection'], $params['dbName']);
    }
    return $this->conn;
  }

  protected function setUp() {
    if (!self::$frameworkReady) {
      TestUtil::initFramework(WCMF_BASE.'app/config/');
      self::$frameworkReady = true;
    }
    parent::setUp();
    $logger = LogManager::getLogger(__CLASS__);
    $logger->info("Running: ".get_class($this).".".$this->getName());
  }

  protected function tearDown() {
    self::$frameworkReady = false;
  }

  /**
   * Get the logger for the given category
   * @param $category
   * @return Logger
   */
  protected function getLogger($category) {
    return LogManager::getLogger($category);
  }
}
?>