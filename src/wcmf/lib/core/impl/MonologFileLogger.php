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
namespace wcmf\lib\core\impl;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;
use wcmf\lib\config\ConfigurationException;
use wcmf\lib\util\StringUtil;

/**
 * MonologFileLogger is a wrapper for the Monolog library that logs to files.
 *
 * Loggers may be configured by passing configuration file name to the first
 * created logger instance. The file must have INI file format. The following
 * sectiona are supported:
 * - _root_:
 *     - _level_: default log level
 *     - _target_: location of rotating log files or stream resource e.g. php://output
 * - _loggers_: Keys are the logger names, values the levels (_DEBUG_, _WARN_, ...)
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class MonologFileLogger implements \wcmf\lib\core\Logger {

  private $_monologLogger = null;

  private static $_defaultLevel = Logger::ERROR;
  private static $_logTarget = '';
  private static $_levels = array();

  /**
   * Constructor
   * @param $name The logger name (channel in Monolog)
   * @param $configFile A configuration file name
   */
  public function __construct($name, $configFile='') {
    if (strlen($configFile) > 0) {
      self::configure($configFile);
    }
    $level = isset(self::$_levels[$name]) ? self::$_levels[$name] : self::$_defaultLevel;

    $output = "[%datetime%] %level_name%: %channel%:%extra.line%: %message%\n";
    $formatter = new LineFormatter($output, null, true);
    $processor = new IntrospectionProcessor($level, array(__CLASS__));
    if (preg_match('/^.+?:\/\//', self::$_logTarget)) {
      $handler = new StreamHandler(self::$_logTarget, $level);
    }
    else {
      $handler = new RotatingFileHandler(self::$_logTarget.'.log', 0, $level);
      $handler->setFilenameFormat('{date}', 'Y-m-d');
    }
    $handler->setFormatter($formatter);
    $handler->pushProcessor($processor);

    $this->_monologLogger = new Logger($name, array($handler));
  }

  /**
   * @see Logger::debug()
   */
  public function debug($message) {
    $this->_monologLogger->addDebug($this->prepareMessage($message));
  }

  /**
   * @see Logger::info()
   */
  public function info($message) {
    $this->_monologLogger->addInfo($this->prepareMessage($message));
  }

  /**
   * @see Logger::warn()
   */
  public function warn($message) {
    $this->_monologLogger->addWarning($this->prepareMessage($message));
  }

  /**
   * @see Logger::error()
   */
  public function error($message) {
    $this->_monologLogger->addError($this->prepareMessage($message));
  }

  /**
   * @see Logger::fatal()
   */
  public function fatal($message) {
    $this->_monologLogger->addCritical($this->prepareMessage($message));
  }

  /**
   * @see Logger::isDebugEnabled()
   */
  public function isDebugEnabled() {
    return self::$_defaultLevel <= Logger::DEBUG;
  }

  /**
   * @see Logger::isInfoEnabled()
   */
  public function isInfoEnabled() {
    return self::$_defaultLevel <= Logger::INFO;
  }

  /**
   * @see Logger::isWarnEnabled()
   */
  public function isWarnEnabled() {
    return self::$_defaultLevel <= Logger::WARNING;
  }

  /**
   * @see Logger::isErrorEnabled()
   */
  public function isErrorEnabled() {
    return self::$_defaultLevel <= Logger::ERROR;
  }

  /**
   * @see Logger::isFatalEnabled()
   */
  public function isFatalEnabled() {
    return self::$_defaultLevel <= Logger::CRITICAL;
  }

  /**
   * @see Logger::getLogger()
   */
  public static function getLogger($name) {
    return new MonologFileLogger($name);
  }

  /**
   * Configure logging
   * @param $configFile
   */
  private function configure($configFile) {
    if (!file_exists($configFile)) {
      throw new ConfigurationException('Configuration file '.$configFile.' not found');
    }
    $config = parse_ini_file($configFile, true);

    // default settings
    if (isset($config['root'])) {
      $rootConfig = $config['root'];
      self::$_defaultLevel = isset($rootConfig['level']) ?
              constant('Monolog\Logger::'.strtoupper($rootConfig['level'])) :
              self::$_defaultLevel;
      self::$_logTarget = isset($rootConfig['target']) ?
              $rootConfig['target'] : WCMF_BASE.self::$_logTarget;
    }

    // log levels
    self::$_levels = isset($config['loggers']) ? $config['loggers'] : array();
    foreach (self::$_levels as $key => $val) {
      self::$_levels[$key] = constant('Monolog\Logger::'.strtoupper($val));
    }
  }

  /**
   * Prepare a message to be used with the internal logger
   * @param $message
   * @return String
   */
  private function prepareMessage($message) {
    return is_string($message) ? $message : StringUtil::getDump($message);
  }
}
?>
