<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2020 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\core;

use wcmf\lib\config\ConfigurationException;

ini_set('html_errors', 'false');

/**
 * LogManager is used to retrieve Logger instances.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class LogManager {

  private static ?Logger $logger = null;

  /**
   * Configure the manager.
   * @param Logger $logger Logger instance
   */
  public static function configure(Logger $logger): void {
    self::$logger = $logger;
  }

  /**
   * Get the logger with the given name
   * @param string $name The logger name
   * @return Logger
   */
  public static function getLogger(string $name): Logger {
    if (self::$logger == null) {
      throw new ConfigurationException('LogManager is not configured. Did you call the configure() method?');
    }
    return self::$logger->getLogger($name);
  }
}
?>
