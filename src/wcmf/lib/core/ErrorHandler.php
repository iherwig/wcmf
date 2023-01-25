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
  use LogTrait;

  /** @var array<int, string> fatal errors that can be handled with a user defined function */
  private static array $fatalErrors = [E_USER_ERROR => '', E_RECOVERABLE_ERROR => ''];

  /**
   * Constructor
   * @param bool $setExceptionHandler Boolean indicating, if this instance should also set
   *                             an exception handler to log exceptions (default: __false__)
   */
  public function __construct(bool $setExceptionHandler=false) {
    set_error_handler([$this, 'handleError']);
    if ($setExceptionHandler) {
      set_exception_handler([$this, 'handleException']);
    }
  }

  /**
   * Get the stack trace
   * @return string The stack trace as string
   */
  public static function getStackTrace(): ?string {
    ob_start();
    debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    $trace = ob_get_contents();
    ob_end_clean();

    // remove first item from backtrace as it's this function which is redundant.
    $trace = ($trace !== false) ? preg_replace('/^#0\s+'.__FUNCTION__."[^\n]*\n/", '', $trace, 1) : '';
    return $trace;
  }

  /**
   * Error handler
   * @param int $errno,
   * @param string $errstr,
   * @param string $errfile,
   * @param int $errline,
   * @param array<mixed> $errcontext
   * @return bool false if the normal error handler should continue
   * @throws \ErrorException
   */
  public function handleError(int $errno, string $errstr, string $errfile='', int $errline=0, array $errcontext=[]): bool {
    $errorIsEnabled = (bool)($errno & (int)ini_get('error_reporting'));

    // FATAL ERROR
    if(isset(self::$fatalErrors[$errno]) && $errorIsEnabled) {
      throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    // NON-FATAL ERROR/WARNING/NOTICE
    else if($errorIsEnabled) {
      $info = $errstr." in ".$errfile." on line ".$errline;
      self::logger()->logByErrorType($errno, $info);
    }
    return true;
  }

  /**
   * Exception handler
   * @param \Throwable $t
   */
  public function handleException(\Throwable $t): void {
    self::logger()->error($t);
  }
}
?>