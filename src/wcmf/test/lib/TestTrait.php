<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2016 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\test\lib;

use wcmf\lib\core\Logger;
use wcmf\lib\core\LogManager;
use wcmf\lib\core\ObjectFactory;

trait TestTrait {

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
    $mapper = $persistenceFacade->getMapper($type);
    return str_replace('`', $mapper->getQuoteIdentifierSymbol(), $sql);
  }
}
?>