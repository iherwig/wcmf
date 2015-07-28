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
namespace wcmf\lib\core;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\core\ObjectFactory;

/**
 * LogManager is used to retrieve Logger instances.
 * Logger objects are instantiated from the Logger configuration section
 * and get the logger name passed additionally.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class LogManager {

  private static $_loggerInstance = null;

  /**
   * Configure the LogManager with a Logger instance
   * @param type $loggerInstance
   */
  public static function configure(Logger $loggerInstance) {
    self::$_loggerInstance = $loggerInstance;
  }

  /**
   * Get the logger with the given name
   * @param $name The logger name
   * @return Logger
   */
  public static function getLogger($name) {
    if (self::$_loggerInstance == null) {
      throw new ConfigurationException('LogManager::configure has to be called before using Logger');
    }
    return self::$_loggerInstance->getLogger($name);
  }
}
?>
