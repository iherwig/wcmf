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
namespace wcmf\lib\core;

ini_set('html_errors', false);

/**
 * LogManager is used to retrieve Logger instances.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class LogManager {

  private static $logger = null;

  /**
   * Configure the manager.
   * @param $logger Logger instance
   */
  public static function configure(Logger $logger) {
    self::$logger = $logger;
  }

  /**
   * Get the logger with the given name
   * @param $name The logger name
   * @return Logger
   */
  public static function getLogger($name) {
    return self::$logger->getLogger($name);
  }
}
?>
