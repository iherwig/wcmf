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
namespace wcmf\lib\core\impl;

use \Logger;

/**
 * Log4phpLogger is a wrapper for the log4php library.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Log4phpLogger implements \wcmf\lib\core\Logger {

  private static $_initialized = false;

  private $_log4phpLogger = null;

  /**
   * Constructor
   */
  public function __construct($name, $configFile='') {
    if (!self::$_initialized) {
      Logger::configure($configFile);
      self::$_initialized = true;
    }
    $name = str_replace('\\', '.', $name);
    $this->_log4phpLogger = Logger::getLogger($name);
  }

  /**
   * @see Logger::trace()
   */
  public function trace($message) {
    $this->_log4phpLogger->trace($message);
  }

  /**
   * @see Logger::debug()
   */
  public function debug($message) {
    $this->_log4phpLogger->debug($message);
  }

  /**
   * @see Logger::info()
   */
  public function info($message) {
    $this->_log4phpLogger->info($message);
  }

  /**
   * @see Logger::warn()
   */
  public function warn($message) {
    $this->_log4phpLogger->warn($message);
  }

  /**
   * @see Logger::error()
   */
  public function error($message) {
    $this->_log4phpLogger->error($message);
  }

  /**
   * @see Logger::fatal()
   */
  public function fatal($message) {
    $this->_log4phpLogger->fatal($message);
  }

  /**
   * @see Logger::isDebugEnabled()
   */
  public function isDebugEnabled() {
    return $this->_log4phpLogger->isDebugEnabled();
  }

  /**
   * @see Logger::isInfoEnabled()
   */
  public function isInfoEnabled() {
    return $this->_log4phpLogger->isInfoEnabled();
  }

  /**
   * @see Logger::isWarnEnabled()
   */
  public function isWarnEnabled() {
    return $this->_log4phpLogger->isWarnEnabled();
  }

  /**
   * @see Logger::isErrorEnabled()
   */
  public function isErrorEnabled() {
    return $this->_log4phpLogger->isErrorEnabled();
  }

  /**
   * @see Logger::isFatalEnabled()
   */
  public function isFatalEnabled() {
    return $this->_log4phpLogger->isFatalEnabled();
  }

  /**
   * @see Logger::getLogger()
   */
  public static function getLogger($name) {
    return new Log4phpLogger($name);
  }
}
?>
