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
namespace wcmf\test\lib;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\util\TestUtil;

/**
 * DatabaseTestCase is the base class for test cases that need database support.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class DatabaseTestCase extends \PHPUnit_Extensions_Database_TestCase {
  use TestTrait;

  private static $frameworkReady = false;

  public final function getConnection() {
    if (!self::$frameworkReady) {
      TestUtil::initFramework(WCMF_BASE.'app/config/');
      self::$frameworkReady = true;
    }
    $params = TestUtil::createDatabase();
    return $this->createDefaultDBConnection($params['connection'], $params['dbName']);
  }

  public function run(\PHPUnit_Framework_TestResult $result=null) {
    $this->setPreserveGlobalState(false);
    return parent::run($result);
  }

  protected function setUp() {
    if (!self::$frameworkReady) {
      TestUtil::initFramework(WCMF_BASE.'app/config/');
      self::$frameworkReady = true;
    }
    parent::setUp();
    $this->getLogger(__CLASS__)->info("Running: ".get_class($this).".".$this->getName());
  }

  protected function tearDown() {
    self::$frameworkReady = false;
  }

  protected function executeSql($type, $sql, $parameters=[]) {
    return ObjectFactory::getInstance('persistenceFacade')->getMapper($type)->executeSql($sql, $parameters);
  }
}
?>