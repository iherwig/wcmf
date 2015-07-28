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

/**
 * Interface for logger implementations.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface Logger {

  /**
   * Print a trace message
   * @param $message The message
   */
  public function trace($message);

  /**
   * Print a debug message
   * @param $message The message
   */
  public function debug($message);

  /**
   * Print a info message
   * @param $message The message
   */
  public function info($message);

  /**
   * Print a warn message
   * @param $message The message
   */
  public function warn($message);

  /**
   * Print a error message
   * @param $message The message
   */
  public function error($message);

  /**
   * Print a fatal message
   * @param $message The message
   */
  public function fatal($message);

  /**
   * Check if debug level is enabled
   * @return Boolean
   */
  public function isDebugEnabled();

  /**
   * Check if info level is enabled
   * @return Boolean
   */
  public function isInfoEnabled();

  /**
   * Check if warn level is enabled
   * @return Boolean
   */
  public function isWarnEnabled();

  /**
   * Check if error level is enabled
   * @return Boolean
   */
  public function isErrorEnabled();

  /**
   * Check if fatal level is enabled
   * @return Boolean
   */
  public function isFatalEnabled();

  /**
   * Get a logger by name
   * @param $name The name
   * @return Logger
   */
  public function getLogger($name);
}
?>
