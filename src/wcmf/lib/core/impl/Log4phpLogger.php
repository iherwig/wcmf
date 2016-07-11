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
namespace wcmf\lib\core\impl;

use \Logger;
use wcmf\lib\core\impl\AbstractLogger;

/**
 * Log4phpLogger is a wrapper for the log4php library.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Log4phpLogger extends AbstractLogger implements \wcmf\lib\core\Logger {

  private static $initialized = false;

  private $log4phpLogger = null;

  /**
   * Constructor
   */
  public function __construct($name, $configFile='') {
    if (!self::$initialized) {
      Logger::configure($configFile);
      self::$initialized = true;
    }
    $name = str_replace('\\', '.', $name);
    $this->log4phpLogger = Logger::getLogger($name);
  }

  /**
   * @see Logger::trace()
   */
  public function trace($message) {
    $this->log4phpLogger->trace($message);
  }

  /**
   * @see Logger::debug()
   */
  public function debug($message) {
    $this->log4phpLogger->debug($message);
  }

  /**
   * @see Logger::info()
   */
  public function info($message) {
    $this->log4phpLogger->info($message);
  }

  /**
   * @see Logger::warn()
   */
  public function warn($message) {
    $this->log4phpLogger->warn($message);
  }

  /**
   * @see Logger::error()
   */
  public function error($message) {
    $this->log4phpLogger->error($message);
  }

  /**
   * @see Logger::fatal()
   */
  public function fatal($message) {
    $this->log4phpLogger->fatal($message);
  }

  /**
   * @see Logger::isDebugEnabled()
   */
  public function isDebugEnabled() {
    return $this->log4phpLogger->isDebugEnabled();
  }

  /**
   * @see Logger::isInfoEnabled()
   */
  public function isInfoEnabled() {
    return $this->log4phpLogger->isInfoEnabled();
  }

  /**
   * @see Logger::isWarnEnabled()
   */
  public function isWarnEnabled() {
    return $this->log4phpLogger->isWarnEnabled();
  }

  /**
   * @see Logger::isErrorEnabled()
   */
  public function isErrorEnabled() {
    return $this->log4phpLogger->isErrorEnabled();
  }

  /**
   * @see Logger::isFatalEnabled()
   */
  public function isFatalEnabled() {
    return $this->log4phpLogger->isFatalEnabled();
  }

  /**
   * @see Logger::getLogger()
   */
  public static function getLogger($name) {
    return new Log4phpLogger($name);
  }
}
?>
