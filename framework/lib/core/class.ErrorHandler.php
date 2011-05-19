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

/**
 * @class ErrorHandler catches all php errors and transforms fatal
 * errors into ErrorExceptions and non-fatal into log messages
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ErrorHandler
{
  public static function handleError($errno, $errstr, $errfile, $errline)
  {
    $errorIsEnabled = (bool)($errno & ini_get('error_reporting'));

    // -- FATAL ERROR
    if(in_array($errno, array(E_USER_ERROR, E_RECOVERABLE_ERROR)) && $errorIsEnabled ) {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    // -- NON-FATAL ERROR/WARNING/NOTICE
    else if( $errorIsEnabled ) {
        Log::warn($errstr, __CLASS__);
        return false; // Make sure this ends up in $php_errormsg, if appropriate
    }
  }
}

set_error_handler(array(new ErrorHandler(), 'handleError'));
?>