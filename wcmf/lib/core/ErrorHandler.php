<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2009 wemove digital solutions GmbH
 *
 * Licensed under the terms of any of the following licenses
 * at your choice:
 *
 * - GNU Lesser General Public License (LGPL)
 *   http://www.gnu.org/licenses/lgpl.html
 * - Eclipse Public License (EPL)
 *   http://www.eclipse.org/org/documents/epl-v10.php
 *
 * See the license.txt file distributed with this work for
 * additional information.
 *
 * $Id$
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
   * Actual error handling method
   * @param type errno
   * @param type errstr
   * @param type errfile
   * @param type errline
   * @return boolean
   * @throws ErrorException
   */
  private function handleError($errno, $errstr, $errfile, $errline) {
    $errorIsEnabled = (bool)($errno & ini_get('error_reporting'));
echo $errstr." ".$errfile." ".$errline;
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

new ErrorHandler();
?>