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

/**
 * ErrorHandler catches all php errors and transforms fatal
 * errors into ErrorExceptions and non-fatal into log messages
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ErrorHandler {

  // fatal errors that can be handled with a user defined function
  private static $fatalErrors = [E_USER_ERROR => '', E_RECOVERABLE_ERROR => ''];

  private static $logger = null;

  /**
   * Constructor
   * @param $setExceptionHandler Boolean indicating, if this instance should also set
   *                             an exception handler to log exceptions (default: __false__)
   */
  public function __construct($setExceptionHandler=false) {
    set_error_handler([$this, 'handleError']);
    if ($setExceptionHandler) {
      set_exception_handler([$this, 'handleException']);
    }
    if (self::$logger == null) {
      self::$logger = LogManager::getLogger(__CLASS__);
    }
  }

  /**
   * Get the stack trace
   * @return The stack trace as string
   */
  public static function getStackTrace() {
    ob_start();
    debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    $trace = ob_get_contents();
    ob_end_clean();

    // remove first item from backtrace as it's this function which is redundant.
    $trace = preg_replace('/^#0\s+'.__FUNCTION__."[^\n]*\n/", '', $trace, 1);

    return $trace;
  }

  /**
   * Error handler
   * @param $errno
   * @param $errstr
   * @param $errfile
   * @param $errline
   * @return Boolean
   * @throws ErrorException
   */
  public function handleError($errno, $errstr, $errfile, $errline) {
    $errorIsEnabled = (bool)($errno & error_reporting());

    // FATAL ERROR
    if(isset(self::$fatalErrors[$errno]) && $errorIsEnabled) {
      throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    // NON-FATAL ERROR/WARNING/NOTICE
    else if($errorIsEnabled) {
      $info = $errstr." in ".$errfile." on line ".$errline;
      self::$logger->logByErrorType($errno, $info);
    }
  }

  /**
   * Exception handler
   * @param $ex
   */
  public function handleException($ex) {
    self::$logger->error($ex);
  }
}
?>