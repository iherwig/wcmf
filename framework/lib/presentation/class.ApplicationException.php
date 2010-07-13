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
require_once(BASE."wcmf/lib/presentation/class.Request.php");
require_once(BASE."wcmf/lib/presentation/class.Response.php");

/**
 * @class ApplicationException
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ApplicationException extends Exception
{
  private $_request = null;
  private $_response = null;
  
  /**
   * Constructor
   * @param request The current request
   * @param response The current response
   * @param message The exception message
   * @param code The exception code
   * @param previous The previous exception
   */
  public function __construct(Request $request, Response $response, $message=null, $code=0)
  {
    $this->_request = $request;
    $this->_response = $response;
    
    parent::__construct($message, $code);
  }
  
  /**
   * Get the current request
   * @return The Request instance
   */
  public function getRequest()
  {
    return $this->_request;
  }

  /**
   * Get the current response
   * @return The Response instance
   */
  public function getResponse()
  {
    return $this->_response;
  }

  /**
   * Get the string representation of the exception code.
   * Exception codes are defined as constants of the form: const CODE_STRING = CODE_NUMBER
   * @return The exception code string
   */
  public function getCodeString()
  {
    $class = new ReflectionClass(get_class($this));
    $codeMap = array_flip($class->getConstants());
    if (isset($codeMap[$this->getCode()])) {
      return $codeMap[$this->getCode()];
    }
    else {
      return '';
    }
  }
}
?>
