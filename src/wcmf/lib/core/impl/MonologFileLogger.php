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
namespace wcmf\lib\core\impl;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;
use wcmf\lib\config\ConfigurationException;
use wcmf\lib\io\FileUtil;

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
class MonologFileLogger extends Logger implements \wcmf\lib\core\Logger {

  const ROOT_SECTION_NAME = 'Root';
  const LOGGER_SECTION_NAME = 'Logger';

  private static int $defaultLevel = Logger::ERROR;
  private static string $logTarget = '';
  private static array $configLevels = [];

  /**
   * Constructor
   * @param string $name The logger name (channel in Monolog)
   * @param string $configFile A configuration file name
   */
  public function __construct(string $name, string $configFile='') {
    if (strlen($configFile) > 0) {
      $this->configure($configFile);
      if (!$this->isStreamTarget(self::$logTarget)) {
        $fileUtil = new FileUtil();
        self::$logTarget = $fileUtil->realpath(WCMF_BASE.self::$logTarget).'/';
        $fileUtil->mkdirRec(self::$logTarget);
      }
    }
    $level = isset(self::$configLevels[$name]) ? self::$configLevels[$name] : self::$defaultLevel;

    $output = "[%datetime%] %level_name%: %channel%:%extra.line%: %message%\n";
    $formatter = new LineFormatter($output, null, true);
    $processor = new IntrospectionProcessor($level, [__CLASS__]);
    if ($this->isStreamTarget(self::$logTarget)) {
      $handler = new StreamHandler(self::$logTarget, $level);
    }
    else {
      $handler = new RotatingFileHandler(self::$logTarget.'.log', 0, $level);
      $handler->setFilenameFormat('{date}', 'Y-m-d');
    }
    $handler->setFormatter($formatter);
    $handler->pushProcessor($processor);

    parent::__construct($name, [$handler], [$processor]);
  }

  /**
   * @see Logger::isInfoEnabled()
   */
  public function isInfoEnabled(): bool {
    return parent::isHandling(self::INFO);
  }

  /**
   * @see Logger::isDebugEnabled()
   */
  public function isDebugEnabled(): bool {
    return parent::isHandling(self::DEBUG);
  }

  /**
   * @see Logger::logByErrorType()
   */
  public function logByErrorType(int $type, string $message): void {
    switch ($type) {
      case E_NOTICE:
      case E_USER_NOTICE:
      case E_STRICT:
      case E_DEPRECATED:
      case E_USER_DEPRECATED:
        $this->info($message);
        break;
      case E_WARNING:
      case E_USER_WARNING:
        $this->warning($message);
        break;
      default:
        $this->error($message);
    }
  }

  /**
   * @see Logger::getLogger()
   */
  public static function getLogger(string $name): \wcmf\lib\core\Logger {
    return new self($name);
  }

  /**
   * Configure logging
   * @param string $configFile
   */
  private function configure(string $configFile): void {
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
    self::$configLevels = isset($config[self::LOGGER_SECTION_NAME]) ?
            $config[self::LOGGER_SECTION_NAME] : [];
    foreach (self::$configLevels as $key => $val) {
      self::$configLevels[$key] = constant('Monolog\Logger::'.strtoupper($val));
    }
  }

  /**
   * Check if the given target is a stream resource
   * @param string $target
   * @return bool
   */
  private function isStreamTarget(string $target): bool {
    return preg_match('/^.+?:\/\//', $target);
  }
}
?>
