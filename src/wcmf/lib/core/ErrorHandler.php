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

use wcmf\lib\core\Log;

/**
 * ErrorHandler catches all php errors and transforms fatal
 * errors into ErrorExceptions and non-fatal into log messages
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ErrorHandler {

  private static $FATAL_ERRORS = array(E_USER_ERROR => '', E_RECOVERABLE_ERROR => '');

  /**
   * Constructor.
   */
  public function __construct() {
    set_error_handler(array($this, 'handleError'));
  }

  /**
   * Get the stack trace
   * @return The stack trace as string
   */
  public static function getStackTrace() {
    ob_start();
    debug_print_backtrace();
    $trace = ob_get_contents();
    ob_end_clean();

    // remove first item from backtrace as it's this function which is redundant.
    $trace = preg_replace ('/^#0\s+' . __FUNCTION__ . "[^\n]*\n/", '', $trace, 1);

    // renumber backtrace items.
    $trace = preg_replace ('/^#(\d+)/me', '\'#\' . ($1 - 1)', $trace);

    return $trace;
  }

  /**
   * Actual error handling method
   * @param $errno
   * @param $errstr
   * @param $errfile
   * @param $errline
   * @return Boolean
   * @throws ErrorException
   */
  public function handleError($errno, $errstr, $errfile, $errline) {
    $errorIsEnabled = (bool)($errno & ini_get('error_reporting'));

    // -- FATAL ERROR
    if(isset(self::$FATAL_ERRORS[$errno]) && $errorIsEnabled ) {
        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    // -- NON-FATAL ERROR/WARNING/NOTICE
    else if( $errorIsEnabled ) {
        Log::warn($errstr, __CLASS__);
        return false; // Make sure this ends up in $php_errormsg, if appropriate
    }
  }
}
?>