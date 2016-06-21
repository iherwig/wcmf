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
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\util\TestUtil;

/**
 * BaseTestCase is the base class for all wCMF test cases.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class BaseTestCase extends \PHPUnit_Framework_TestCase {

  protected function setUp() {
    TestUtil::initFramework(WCMF_BASE.'app/config/');
    parent::setUp();
    $logger = LogManager::getLogger(__CLASS__);
    $logger->info("Running: ".get_class($this).".".$this->getName());
  }

  /**
   * Get the logger for the given category
   * @param $category
   * @return Logger
   */
  protected function getLogger($category) {
    return LogManager::getLogger($category);
  }

  /**
   * Replace backticks in the given sql string by the actual quote char
   * used in the connection
   * @param $sql
   * @param $type The type defining the connection parameters
   * @return String
   */
  protected function fixQueryQuotes($sql, $type) {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $connection = $persistenceFacade->getMapper($type)->getConnection();
    return str_replace('`', $connection->getQuoteIdentifierSymbol(), $sql);
  }
}
?>