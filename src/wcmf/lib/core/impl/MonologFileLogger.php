<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2017 wemove digital solutions GmbH
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
use wcmf\lib\core\impl\AbstractLogger;
use wcmf\lib\io\FileUtil;
use wcmf\lib\util\StringUtil;

/**
 * MonologFileLogger is a wrapper for the Monolog library that logs to files.
 *
 * Loggers may be configured by passing configuration file name to the first
 * created logger instance. The file must have INI file format. The following
 * sectiona are supported:
 * - _Root_:
 *     - _level_: default log level
 *     - _target_: location of rotating log files relative to WCMF_BASE or stream resource e.g. php://stdout
 * - _Logger_: Keys are the logger names, values the levels (_DEBUG_, _WARN_, ...)
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class MonologFileLogger extends AbstractLogger implements \wcmf\lib\core\Logger {

  const ROOT_SECTION_NAME = 'Root';
  const LOGGER_SECTION_NAME = 'Logger';

  private $monologLogger = null;
  private $level = Logger::ERROR;

  private static $defaultLevel = Logger::ERROR;
  private static $logTarget = '';
  private static $levels = array();

  /**
   * Constructor
   * @param $name The logger name (channel in Monolog)
   * @param $configFile A configuration file name
   */
  public function __construct($name, $configFile='') {
    if (strlen($configFile) > 0) {
      self::configure($configFile);
      if (!$this->isStreamTarget(self::$logTarget)) {
        $fileUtil = new FileUtil();
        self::$logTarget = $fileUtil->realpath(WCMF_BASE.self::$logTarget).'/';
        $fileUtil->mkdirRec(self::$logTarget);
      }
    }
    $level = isset(self::$levels[$name]) ? self::$levels[$name] : self::$defaultLevel;
    $this->level = $level;

    $output = "[%datetime%] %level_name%: %channel%:%extra.line%: %message%\n";
    $formatter = new LineFormatter($output, null, true);
    $processor = new IntrospectionProcessor($level, array(__CLASS__));
    if ($this->isStreamTarget(self::$logTarget)) {
      $handler = new StreamHandler(self::$logTarget, $level);
    }
    else {
      $handler = new RotatingFileHandler(self::$logTarget.'.log', 0, $level);
      $handler->setFilenameFormat('{date}', 'Y-m-d');
    }
    $handler->setFormatter($formatter);
    $handler->pushProcessor($processor);

    $this->monologLogger = new Logger($name, array($handler));
  }

  /**
   * @see Logger::debug()
   */
  public function debug($message) {
    $this->monologLogger->addDebug($this->prepareMessage($message));
  }

  /**
   * @see Logger::info()
   */
  public function info($message) {
    $this->monologLogger->addInfo($this->prepareMessage($message));
  }

  /**
   * @see Logger::warn()
   */
  public function warn($message) {
    $this->monologLogger->addWarning($this->prepareMessage($message));
  }

  /**
   * @see Logger::error()
   */
  public function error($message) {
    $this->monologLogger->addError($this->prepareMessage($message));
  }

  /**
   * @see Logger::fatal()
   */
  public function fatal($message) {
    $this->monologLogger->addCritical($this->prepareMessage($message));
  }

  /**
   * @see Logger::isDebugEnabled()
   */
  public function isDebugEnabled() {
    return $this->level <= Logger::DEBUG;
  }

  /**
   * @see Logger::isInfoEnabled()
   */
  public function isInfoEnabled() {
    return $this->level <= Logger::INFO;
  }

  /**
   * @see Logger::isWarnEnabled()
   */
  public function isWarnEnabled() {
    return $this->level <= Logger::WARNING;
  }

  /**
   * @see Logger::isErrorEnabled()
   */
  public function isErrorEnabled() {
    return $this->level <= Logger::ERROR;
  }

  /**
   * @see Logger::isFatalEnabled()
   */
  public function isFatalEnabled() {
    return $this->level <= Logger::CRITICAL;
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
    if (isset($config[self::ROOT_SECTION_NAME])) {
      $rootConfig = $config[self::ROOT_SECTION_NAME];
      self::$defaultLevel = isset($rootConfig['level']) ?
              constant('Monolog\Logger::'.strtoupper($rootConfig['level'])) :
              self::$defaultLevel;
      self::$logTarget = isset($rootConfig['target']) ?
              $rootConfig['target'] : WCMF_BASE.self::$logTarget;
    }

    // log levels
    self::$levels = isset($config[self::LOGGER_SECTION_NAME]) ?
            $config[self::LOGGER_SECTION_NAME] : array();
    foreach (self::$levels as $key => $val) {
      self::$levels[$key] = constant('Monolog\Logger::'.strtoupper($val));
    }
  }

  /**
   * Prepare a message to be used with the internal logger
   * @param $message
   * @return String
   */
  private function prepareMessage($message) {
    return is_string($message) ? $message :
      (is_object($message) && method_exists($message, '__toString') ? $message->__toString() :
            StringUtil::getDump($message));
  }

  /**
   * Check if the given target is a stream resource
   * @param $target
   * @return Boolean
   */
  private function isStreamTarget($target) {
    return preg_match('/^.+?:\/\//', $target);
  }
}
?>
